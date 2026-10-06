<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Line items of every invoice (Invoice and A Invoice each get their own rows).
 * price_per_mtr / amount = this invoice's share (split %) of the quotation item.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('despatch_to')->nullable();
            $table->decimal('size_mtr', 10, 2);
            $table->unsignedInteger('no_of_rolls');
            $table->decimal('total_mtr', 12, 2);
            $table->decimal('quotation_price_per_mtr', 12, 2); // original quotation rate
            $table->decimal('quotation_amount', 15, 2);        // original quotation amount
            $table->decimal('price_per_mtr', 12, 2);           // rate on this invoice
            $table->decimal('amount', 15, 2);                  // amount on this invoice
            $table->timestamps();
        });

        // Backfill details for invoices that already exist.
        $invoices = DB::table('invoices')->get();
        foreach ($invoices as $invoice) {
            $type = $invoice->invoice_type ?? 'gst';
            $pct = (float) ($invoice->split_percentage ?? 100);
            $items = DB::table('quotation_items')->where('quotation_id', $invoice->quotation_id)->orderBy('id')->get();

            foreach ($items as $item) {
                DB::table('invoice_details')->insert([
                    'invoice_id' => $invoice->id,
                    'quotation_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'despatch_to' => $item->despatch_to ?? null,
                    'size_mtr' => $item->size_mtr,
                    'no_of_rolls' => $item->no_of_rolls,
                    'total_mtr' => $item->total_mtr,
                    'quotation_price_per_mtr' => $item->price_per_mtr,
                    'quotation_amount' => $item->amount,
                    'price_per_mtr' => self::split((float) $item->price_per_mtr, $type, $pct),
                    'amount' => self::split((float) $item->amount, $type, $pct),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }

    private static function split(float $value, string $type, float $pct): float
    {
        if ($pct >= 100) {
            return round($value, 2);
        }

        return $type === 'gst'
            ? round($value * $pct / 100, 2)
            : round($value - round($value * (100 - $pct) / 100, 2), 2);
    }
};
