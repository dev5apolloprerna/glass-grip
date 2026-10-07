<?php

namespace App\Http\Controllers;

use App\Models\CustomerLedger;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);
        $invoice->load(['customer', 'details.product', 'quotation.user', 'payments.enteredBy', 'deliveryChallan']);

        $totalPaid = $invoice->totalPaid();
        $balanceDue = $invoice->balanceDue();

        return view('invoices.show', compact('invoice', 'totalPaid', 'balanceDue'));
    }

    public function download(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);
        $invoice->load(['customer', 'details.product', 'quotation.user']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4');

        return $pdf->stream($invoice->invoice_number . '.pdf');
    }
    /**
     * Invoice number, invoice date and reference no. can be edited at any time.
     */
    public function edit(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);
        $invoice->load(['customer', 'quotation']);

        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        $request->merge([
            // Base number only - the A Invoice automatically gets "-A".
            'invoice_number' => Invoice::baseNumber((string) $request->input('invoice_number')),
            'other_reference' => trim((string) $request->input('other_reference')),
        ]);

        // Invoice and A Invoice of the same quotation share number, date and reference.
        $siblings = Invoice::where('quotation_id', $invoice->quotation_id)->get();

        $data = $request->validate([
            'invoice_number' => [
                'required', 'string', 'max:250',
                function ($attribute, $value, $fail) use ($siblings) {
                    $numbers = [Invoice::numberFor($value, Invoice::TYPE_GST), Invoice::numberFor($value, Invoice::TYPE_NON_GST)];
                    if (Invoice::whereIn('invoice_number', $numbers)->whereNotIn('id', $siblings->pluck('id')->all())->exists()) {
                        $fail('This invoice number is already used.');
                    }
                },
            ],
            'invoice_date' => ['required', 'date'],
            'other_reference' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($siblings, $data) {
            foreach ($siblings as $inv) {
                $inv->update([
                    'invoice_number' => Invoice::numberFor($data['invoice_number'], $inv->invoice_type ?? Invoice::TYPE_GST),
                    'invoice_date' => $data['invoice_date'],
                    'other_reference' => $data['other_reference'] ?: null,
                ]);

                // Keep the customer ledger entry in sync with the new number / date.
                CustomerLedger::where('reference_type', 'invoice')
                    ->where('reference_id', $inv->id)
                    ->update([
                        'transaction_date' => $inv->invoice_date,
                        'description' => ($inv->isGst() ? 'Invoice ' : 'A Invoice ') . $inv->invoice_number,
                    ]);
            }
        });

        return redirect()->route('quotations.show', $invoice->quotation_id)
            ->with('success', $siblings->count() > 1 ? 'Invoice and A Invoice updated successfully.' : 'Invoice updated successfully.');
    }

    public function markSent(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);
        $invoice->update(['document_status' => 'invoice_approved']);

        return back()->with('success', 'Invoice sent and approved.');
    }


    private function authorizeAccess(Invoice $invoice): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && $invoice->quotation->user_id !== $user->id) {
            abort(403, 'You do not have permission to access this invoice.');
        }
    }
}
