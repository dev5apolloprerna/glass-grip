<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;

/**
 * Splits one quotation into an Invoice (p%) and an A Invoice (100 - p%).
 *
 * Nothing is re-calculated - every figure is taken from the saved quotation:
 *  - item amounts, sub total, discount, admin + material handling charges,
 *    CGST / SGST / IGST -> ALL split by the given %
 *  - Invoice  : p% of every figure
 *  - A Invoice: the exact remainder of every figure, and its total is
 *    (quotation net amount - Invoice total), so
 *    Invoice + A Invoice = quotation Net Amount, always (no rounding gap).
 *
 * Example: Net 2,66,680 (taxable 2,26,000 + CGST 20,340 + SGST 20,340), 65 / 35
 *  - Invoice   : 1,46,900 + 26,442 GST = 1,73,342
 *  - A Invoice :   79,100 + 14,238 GST =   93,338
 *  - Total     :                         2,66,680
 */
class InvoiceSplitter
{
    public static function amounts(Quotation $quotation, string $type, float $percentage): array
    {
        $quotation->loadMissing('items');
        $split = fn ($value) => Invoice::splitValue((float) $value, $type, $percentage);

        $subTotal = round(array_sum(array_column(self::details($quotation, $type, $percentage), 'amount')), 2);

        $discount = $split($quotation->discount_amount);
        $admin = $split($quotation->admin_charges);
        $handling = $split($quotation->material_handling_charges);
        $taxable = round($subTotal - $discount + $admin + $handling, 2);

        // GST is split by % on BOTH invoices so the totals match the quotation.
        $cgst = $split($quotation->cgst_amount);
        $sgst = $split($quotation->sgst_amount);
        $igst = $split($quotation->igst_amount);
        $gst = round($cgst + $sgst + $igst, 2);

        $beforeRounding = $taxable + $gst;

        if ($type === Invoice::TYPE_NON_GST && $percentage < 100) {
            // A Invoice takes whatever is left of the quotation's net amount.
            $invoiceTotal = self::amounts($quotation, Invoice::TYPE_GST, round(100 - $percentage, 2))['total_amount'];
            $total = round((float) $quotation->total_amount - $invoiceTotal, 2);
        } else {
            $total = round($beforeRounding);
        }

        return [
            'sub_total' => $subTotal,
            'discount_amount' => $discount,
            'admin_charges' => $admin,
            'material_handling_charges' => $handling,
            'gst_amount' => $gst,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'round_off' => round($total - $beforeRounding, 2),
            'total_amount' => $total,
        ];
    }

    /**
     * Rows for invoice_details: each quotation item with this invoice's share
     * of the rate and amount (quantities stay the same on both invoices).
     */
    public static function details(Quotation $quotation, string $type, float $percentage): array
    {
        $quotation->loadMissing('items');
        $split = fn ($value) => Invoice::splitValue((float) $value, $type, $percentage);

        return $quotation->items->map(fn ($item) => [
            'quotation_item_id' => $item->id,
            'product_id' => $item->product_id,
            'despatch_to' => $item->despatch_to,
            'size_mtr' => $item->size_mtr,
            'no_of_rolls' => $item->no_of_rolls,
            'total_mtr' => $item->total_mtr,
            'quotation_price_per_mtr' => $item->price_per_mtr,
            'quotation_amount' => $item->amount,
            'price_per_mtr' => $split($item->price_per_mtr),
            'amount' => $split($item->amount),
        ])->values()->all();
    }
}
