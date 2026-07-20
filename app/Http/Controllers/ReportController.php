<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Customer (vendor) ledger history report - filter by customer + date range.
     */
    public function customerLedger(Request $request)
    {
        $customers = Customer::orderBy('name')->get();

        $customerId = $request->get('customer_id');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $ledgers = collect();
        $selectedCustomer = null;
        $openingBalanceBeforeRange = 0;

        if ($customerId) {
            $selectedCustomer = Customer::findOrFail($customerId);

            $query = $selectedCustomer->ledgers()->with('enteredBy')->orderBy('transaction_date')->orderBy('id');

            if ($fromDate) {
                $openingBalanceBeforeRange = (float) $selectedCustomer->opening_balance
                    + (float) $selectedCustomer->ledgers()->where('transaction_date', '<', $fromDate)->sum('amount');

                $query->where('transaction_date', '>=', $fromDate);
            } else {
                $openingBalanceBeforeRange = (float) $selectedCustomer->opening_balance;
            }

            if ($toDate) {
                $query->where('transaction_date', '<=', $toDate);
            }

            $ledgers = $query->get();
        }

        return view('reports.customer-ledger', compact(
            'customers', 'ledgers', 'selectedCustomer', 'customerId', 'fromDate', 'toDate', 'openingBalanceBeforeRange'
        ));
    }

    /**
     * Sales report - filter by date range (based on invoice date).
     */
    public function sales(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $customerId = $request->get('customer_id');

        $customers = Customer::orderBy('name')->get();

        $invoices = Invoice::with(['customer', 'quotation.user'])
            ->when($fromDate, fn ($q) => $q->where('invoice_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->where('invoice_date', '<=', $toDate))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderBy('invoice_date')
            ->get();

        $totals = [
            'sub_total' => $invoices->sum('sub_total'),
            'gst_amount' => $invoices->sum('gst_amount'),
            'total_amount' => $invoices->sum('total_amount'),
            'count' => $invoices->count(),
        ];

        return view('reports.sales', compact('invoices', 'totals', 'customers', 'fromDate', 'toDate', 'customerId'));
    }
}
