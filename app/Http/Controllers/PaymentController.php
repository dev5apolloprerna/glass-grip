<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Record a payment collected against a specific approved quotation's invoice.
     */
        public function index(Request $request)
    {
        $user = Auth::user();
        $employees = $user->isSuperAdmin()
            ? User::where('status', 'active')->orderBy('name')->get()
            : collect([$user]);

        $employeeId = $user->isSuperAdmin()
            ? (int) ($request->integer('employee_id') ?: ($employees->first()?->id ?? 0))
            : $user->id;

        abort_unless($employees->contains('id', $employeeId), 403);

        $customers = Customer::query()
            ->whereHas('invoices.quotation', fn ($query) => $query->where('user_id', $employeeId))
            ->withSum(['invoices as billed_amount' => fn ($query) => $query->whereHas('quotation', fn ($q) => $q->where('user_id', $employeeId))], 'total_amount')
            ->withSum(['payments as customer_paid_amount' => fn ($query) => $query->whereNull('invoice_id')->where('employee_id', $employeeId)], 'amount')
            ->orderBy('name')
            ->get();

        $invoicePayments = Payment::query()
            ->whereNotNull('invoice_id')
            ->whereHas('invoice.quotation', fn ($query) => $query->where('user_id', $employeeId))
            ->selectRaw('customer_id, SUM(amount) as total')
            ->groupBy('customer_id')
            ->pluck('total', 'customer_id');

        foreach ($customers as $customer) {
            $customer->collected_amount = (float) ($invoicePayments[$customer->id] ?? 0) + (float) ($customer->customer_paid_amount ?? 0);
            $customer->due_amount = max(0, (float) ($customer->billed_amount ?? 0) - $customer->collected_amount);
        }

        $totals = [
            'billed' => $customers->sum('billed_amount'),
            'collected' => $customers->sum('collected_amount'),
            'due' => $customers->sum('due_amount'),
        ];

        $recentPayments = Payment::with(['customer', 'employee'])
            ->whereNull('invoice_id')
            ->where('employee_id', $employeeId)
            ->latest('payment_date')->latest('id')->limit(20)->get();

        return view('payments.index', compact('employees', 'employeeId', 'customers', 'totals', 'recentPayments'));
    }

    public function storeCustomerPayment(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,cheque,bank_transfer,upi,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employeeId = (int) $data['employee_id'];
        abort_if(! $user->isSuperAdmin() && $employeeId !== $user->id, 403);
        abort_unless(Invoice::where('customer_id', $data['customer_id'])->whereHas('quotation', fn ($q) => $q->where('user_id', $employeeId))->exists(), 422, 'This customer is not assigned to the selected employee.');

        $customer = Customer::findOrFail($data['customer_id']);
        $amount = (float) $data['amount'];
        if ($amount > max(0, $customer->currentBalance()) + 0.01) {
            return back()->withErrors(['amount' => 'Amount cannot exceed the customer balance (₹'.number_format(max(0, $customer->currentBalance()), 2).').'])->withInput();
        }

        $payment = DB::transaction(function () use ($data, $employeeId, $customer, $amount) {
            $payment = Payment::create([
                'customer_id' => $customer->id,
                'employee_id' => $employeeId,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'entered_by' => Auth::id(),
            ]);
            $payment->update(['receipt_number' => 'PR-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)]);

            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_date' => $payment->payment_date,
                'amount' => -abs($amount),
                'description' => 'Customer payment received - Receipt '.$payment->receipt_number,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'entered_by' => Auth::id(),
                'balance_after' => $customer->currentBalance() - $amount,
            ]);

            return $payment;
        });

        return redirect()->route('payment-collections.index', ['employee_id' => $employeeId])
            ->with('success', 'Payment collected successfully. Receipt '.$payment->receipt_number.' generated.');
    }

    public function receipt(Payment $payment)
    {
        abort_unless($payment->invoice_id === null && $payment->receipt_number, 404);
        $user = Auth::user();
        abort_if(! $user->isSuperAdmin() && $payment->employee_id !== $user->id, 403);
        $payment->load(['customer', 'employee', 'enteredBy']);

        return Pdf::loadView('payments.receipt', compact('payment'))->setPaper('a5')->download($payment->receipt_number.'.pdf');
    }

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

        $amount = (float) $data['amount'];
        $balanceDue = $invoice->balanceDue();

        if ($amount > $balanceDue + 0.01) {
            return back()->withErrors(['amount' => 'Amount cannot exceed the balance due (₹' . number_format($balanceDue, 2) . ').'])->withInput();
        }

        DB::transaction(function () use ($invoice, $data, $amount) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'entered_by' => Auth::id(),
            ]);

            $customer = $invoice->customer;
            $newBalance = $customer->currentBalance() - $amount;

            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_date' => $payment->payment_date,
                'amount' => -abs($amount),
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
