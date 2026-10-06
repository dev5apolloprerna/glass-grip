<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    protected $fillable = [
        'invoice_id',
        'quotation_item_id',
        'product_id',
        'despatch_to',
        'size_mtr',
        'no_of_rolls',
        'total_mtr',
        'quotation_price_per_mtr',
        'quotation_amount',
        'price_per_mtr',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'size_mtr' => 'decimal:2',
            'total_mtr' => 'decimal:2',
            'quotation_price_per_mtr' => 'decimal:2',
            'quotation_amount' => 'decimal:2',
            'price_per_mtr' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function quotationItem()
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
