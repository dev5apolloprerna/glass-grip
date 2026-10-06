<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;

/**
 * Splits one quotation into an Invoice (p%) and an A Invoice (100 - p%).
 *
 * Nothing is re-calculated - every figure is taken from the saved quotation:
 *  - item amounts, sub total, discount, admin + material handling charges -> split by %
 *  - Invoice  : p% of the quotation's CGST / SGST / IGST (same GST as the quotation)
 *  - A Invoice: no GST
 *  - each invoice is rounded to the nearest rupee.
 *
 * Example: quotation 36,000 + GST 6,480 = 42,480, split 60 / 40
 *  - Invoice   : 21,600 + GST 3,888 = 25,488
 *  - A Invoice : 14,400 (no GST)
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

        $isGst = $type === Invoice::TYPE_GST;
        $cgst = $isGst ? $split($quotation->cgst_amount) : 0.0;
        $sgst = $isGst ? $split($quotation->sgst_amount) : 0.0;
        $igst = $isGst ? $split($quotation->igst_amount) : 0.0;
        $gst = round($cgst + $sgst + $igst, 2);

        $beforeRounding = $taxable + $gst;
        $total = round($beforeRounding);

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
