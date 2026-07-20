<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_number',
        'customer_id',
        'user_id',
        'quotation_date',
        'status',
        'gst_applicable',
        'sub_total',
        'gst_amount',
        'total_amount',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'gst_applicable' => 'boolean',
            'sub_total' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    const GST_RATE = 18;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Recalculate sub_total / gst_amount / total_amount from line items.
     */
    public function recalculateTotals(): void
    {
        $subTotal = $this->items()->sum('amount');
        $gstAmount = $this->gst_applicable ? round($subTotal * self::GST_RATE / 100, 2) : 0;

        $this->sub_total = $subTotal;
        $this->gst_amount = $gstAmount;
        $this->total_amount = $subTotal + $gstAmount;
        $this->save();
    }
}
