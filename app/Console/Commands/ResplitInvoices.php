<?php

namespace App\Console\Commands;

use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\InvoiceSplitter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time fix for invoices generated before the new split logic:
 *  - A Invoice number gets "-A"           (INV-002 -> INV-002-A)
 *  - A Invoice gets its % share of GST, and Invoice + A Invoice = quotation Net Amount
 *  - customer ledger amounts / running balances are corrected by the difference
 *
 * Usage:
 *   php artisan invoices:resplit --dry-run      (show changes only)
 *   php artisan invoices:resplit                (all quotations)
 *   php artisan invoices:resplit --quotation=14 (one quotation)
 */
class ResplitInvoices extends Command
{
    protected $signature = 'invoices:resplit {--quotation= : Only this quotation id} {--dry-run : Show changes without saving}';

    protected $description = 'Re-split existing Invoice / A Invoice amounts and add the -A suffix to A Invoice numbers';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $quotations = Quotation::whereHas('invoices')
            ->when($this->option('quotation'), fn ($q, $id) => $q->whereKey($id))
            ->with(['items', 'invoices.payments'])
            ->get();

        foreach ($quotations as $quotation) {
            DB::transaction(function () use ($quotation, $dry) {
                foreach ($quotation->invoices as $invoice) {
                    $type = $invoice->invoice_type ?? Invoice::TYPE_GST;
                    $amounts = InvoiceSplitter::amounts($quotation, $type, (float) ($invoice->split_percentage ?? 100));
                    $number = Invoice::numberFor($invoice->invoice_number, $type);
                    $delta = round($amounts['total_amount'] - (float) $invoice->total_amount, 2);

                    $this->line(sprintf(
                        '%s  %-12s %-14s -> %-14s  %12s -> %12s',
                        $quotation->quotation_number, $invoice->typeLabel(), $invoice->invoice_number, $number,
                        number_format((float) $invoice->total_amount, 2), number_format($amounts['total_amount'], 2)
                    ));

                    if ($invoice->totalPaid() > $amounts['total_amount'] + 0.01) {
                        $this->warn('   skipped: payments received are more than the new total.');
                        continue;
                    }

                    if ($dry) {
                        continue;
                    }

                    $invoice->update(['invoice_number' => $number, ...$amounts]);

                    $ledger = CustomerLedger::where('reference_type', 'invoice')->where('reference_id', $invoice->id)->first();

                    if ($ledger) {
                        $ledger->update([
                            'amount' => $amounts['total_amount'],
                            'description' => $invoice->typeLabel() . ' ' . $number,
                        ]);

                        if ($delta != 0) {
                            // Keep running balances of this and later entries correct.
                            CustomerLedger::where('customer_id', $ledger->customer_id)
                                ->where('id', '>=', $ledger->id)
                                ->update(['balance_after' => DB::raw('balance_after + ' . $delta)]);
                        }
                    }
                }
            });
        }

        $this->info($dry ? 'Dry run - nothing saved.' : 'Done.');

        return self::SUCCESS;
    }
}
