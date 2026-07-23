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
        'discount_amount',
        'round_off',
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
            'discount_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
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
     * Recalculate sub_total / gst_amount / round_off / total_amount from line items.
     * Order: sub total -> - discount (manual, optional, overall) -> + GST (calculated
     * on the discounted amount) -> round to nearest whole rupee, recording the
     * adjustment as round_off.
     */
    public function recalculateTotals(): void
    {
        $subTotal = $this->items()->sum('amount');
        $discount = (float) ($this->discount_amount ?? 0);
        $discountedSubTotal = $subTotal - $discount;
        $gstAmount = $this->gst_applicable ? round($discountedSubTotal * self::GST_RATE / 100, 2) : 0;

        $beforeRounding = $discountedSubTotal + $gstAmount;
        $roundedTotal = round($beforeRounding);
        $roundOff = round($roundedTotal - $beforeRounding, 2);

        $this->sub_total = $subTotal;
        $this->gst_amount = $gstAmount;
        $this->discount_amount = $discount;
        $this->round_off = $roundOff;
        $this->total_amount = $roundedTotal;
        $this->save();
    }
}
