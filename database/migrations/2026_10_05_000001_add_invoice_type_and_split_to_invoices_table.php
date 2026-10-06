<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One quotation can now produce two invoices:
 *  - "gst"     => general Invoice (GST same as quotation)
 *  - "non_gst" => "A Invoice" (without GST)
 * split_percentage = share of the quotation value billed on this invoice.
 * Both invoices of a quotation share the same invoice number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // quotation_id was unique (one invoice per quotation) - allow two now.
            $table->dropForeign(['quotation_id']);
            $table->dropUnique(['quotation_id']);
            // Same invoice number is used on the GST invoice and the A invoice.
            $table->dropUnique(['invoice_number']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();

            if (! Schema::hasColumn('invoices', 'invoice_type')) {
                $table->string('invoice_type', 20)->default('gst')->after('invoice_number');
            }
            if (! Schema::hasColumn('invoices', 'split_percentage')) {
                $table->decimal('split_percentage', 5, 2)->default(100)->after('invoice_type');
            }
            if (! Schema::hasColumn('invoices', 'other_reference')) {
                $table->string('other_reference')->nullable()->after('split_percentage');
            }
            if (! Schema::hasColumn('invoices', 'admin_charges')) {
                $table->decimal('admin_charges', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('invoices', 'material_handling_charges')) {
                $table->decimal('material_handling_charges', 15, 2)->default(0);
            }

            $table->unique(['quotation_id', 'invoice_type']);
            $table->unique(['invoice_number', 'invoice_type']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
            $table->dropUnique(['quotation_id', 'invoice_type']);
            $table->dropUnique(['invoice_number', 'invoice_type']);
            $table->dropColumn(['invoice_type', 'split_percentage']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('quotation_id');
            $table->unique('invoice_number');
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();
        });
    }
};
