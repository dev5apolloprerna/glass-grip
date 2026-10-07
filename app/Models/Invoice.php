<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'invoice_type',
        'split_percentage',
        'other_reference',
        'quotation_id',
        'customer_id',
        'invoice_date',
        'sub_total',
        'gst_amount',
        'discount_amount',
        'admin_charges',
        'material_handling_charges',
        'round_off',
        'total_amount',
        'shipping_address', 'shipping_address_line_2', 'shipping_state', 'shipping_city', 'shipping_pincode',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'document_status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'split_percentage' => 'decimal:2',
            'sub_total' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2', 'sgst_amount' => 'decimal:2', 'igst_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'admin_charges' => 'decimal:2',
            'material_handling_charges' => 'decimal:2',
            'round_off' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    const TYPE_GST = 'gst';         // General invoice
    const TYPE_NON_GST = 'non_gst'; // A Invoice

    /** Suffix added to the A Invoice number, e.g. INV-002 -> INV-002-A */
    const A_SUFFIX = '-A';

    /** Base number without the A suffix: "INV-002-A" -> "INV-002". */
    public static function baseNumber(string $number): string
    {
        $number = trim($number);

        return str_ends_with(strtoupper($number), self::A_SUFFIX)
            ? substr($number, 0, -strlen(self::A_SUFFIX))
            : $number;
    }

    /** Invoice number for a type: Invoice -> INV-002, A Invoice -> INV-002-A. */
    public static function numberFor(string $base, string $type): string
    {
        $base = self::baseNumber($base);

        return $type === self::TYPE_NON_GST ? $base . self::A_SUFFIX : $base;
    }

    public function isGst(): bool
    {
        return ($this->invoice_type ?? self::TYPE_GST) === self::TYPE_GST;
    }

    public function typeLabel(): string
    {
        return $this->isGst() ? 'Invoice' : 'A Invoice';
    }

    public function documentTitle(): string
    {
        return $this->isGst() && (float) $this->gst_amount > 0 ? 'Tax Invoice' : 'Invoice';
    }

    /**
     * Share of a quotation value on THIS invoice (nothing is re-calculated).
     * Invoice gets p%, A Invoice gets the exact remainder, so
     * Invoice + A Invoice always equals the quotation value.
     */
    public static function splitValue(float $value, string $type, float $pct): float
    {
        if ($pct >= 100) {
            return round($value, 2);
        }

        return $type === self::TYPE_GST
            ? round($value * $pct / 100, 2)
            : round($value - round($value * (100 - $pct) / 100, 2), 2);
    }

    public function share(float $value): float
    {
        return self::splitValue($value, $this->invoice_type ?? self::TYPE_GST, (float) ($this->split_percentage ?? 100));
    }

    /** Line items of this invoice (stored in invoice_details). */
    public function details()
    {
        return $this->hasMany(InvoiceDetail::class)->orderBy('id');
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
    
    public function deliveryChallan() { return $this->hasOne(DeliveryChallan::class); }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return (float) $this->total_amount - $this->totalPaid();
    }
}
