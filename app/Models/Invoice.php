<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'quotation_id',
        'customer_id',
        'invoice_date',
        'sub_total',
        'gst_amount',
        'discount_amount',
        'round_off',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'sub_total' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return (float) $this->total_amount - $this->totalPaid();
    }
}
