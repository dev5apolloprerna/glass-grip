<?php

namespace App\Http\Controllers;

use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Record a payment collected against a specific approved quotation's invoice.
     */
    public function store(Request $request, Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,cheque,bank_transfer,upi,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'entered_by' => Auth::id(),
            ]);

            $customer = $invoice->customer;
            $newBalance = $customer->currentBalance() - (float) $payment->amount;

            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_date' => $payment->payment_date,
                'amount' => -abs($payment->amount),
                'description' => 'Payment received for Invoice ' . $invoice->invoice_number,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'entered_by' => Auth::id(),
                'balance_after' => $newBalance,
            ]);
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    /**
     * Remove a payment entry (corrections) - Super Admin only, also reverses the ledger entry.
     */
    public function destroy(Payment $payment)
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can delete a payment entry.');
        }

        DB::transaction(function () use ($payment) {
            CustomerLedger::where('reference_type', 'payment')
                ->where('reference_id', $payment->id)
                ->delete();

            $payment->delete();
        });

        return back()->with('success', 'Payment entry removed.');
    }

    private function authorizeAccess(Invoice $invoice): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && $invoice->quotation->user_id !== $user->id) {
            abort(403, 'You do not have permission to record payments for this invoice.');
        }
    }
}
