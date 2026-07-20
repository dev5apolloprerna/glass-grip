<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);
        $invoice->load(['customer', 'quotation.items.product', 'quotation.user']);

        return view('invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);
        $invoice->load(['customer', 'quotation.items.product', 'quotation.user']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4');

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    private function authorizeAccess(Invoice $invoice): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && $invoice->quotation->user_id !== $user->id) {
            abort(403, 'You do not have permission to access this invoice.');
        }
    }
}
