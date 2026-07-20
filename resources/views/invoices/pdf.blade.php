<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number ?? '' }}</title>

    <style>
        @page {
            margin: 14mm 12mm 14mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: #172033;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
        }

        .invoice-shell {
            border: 1px solid #cfd6e2;
        }

        .top-band {
            background: #14213d;
            color: #ffffff;
        }

        .top-band td {
            padding: 14px 16px;
        }

        .logo-cell {
            width: 58%;
        }

        .title-cell {
            width: 42%;
            text-align: right;
        }

        .brand-name {
            margin: 0 0 3px 0;
            font-size: 19px;
            font-weight: bold;
            letter-spacing: .2px;
        }

        .brand-subtitle {
            margin: 0;
            color: #dce5f4;
            font-size: 9px;
        }

        .invoice-title {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1.4px;
        }

        .invoice-copy {
            margin-top: 4px;
            color: #dce5f4;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .section-table td {
            border-right: 1px solid #cfd6e2;
            border-bottom: 1px solid #cfd6e2;
        }

        .section-table td:last-child {
            border-right: 0;
        }

        .party-cell {
            width: 57%;
            padding: 12px 14px;
        }

        .meta-cell {
            width: 43%;
            padding: 0;
        }

        .section-label {
            display: inline-block;
            margin-bottom: 7px;
            padding: 3px 7px;
            border-radius: 2px;
            background: #eef2f7;
            color: #314158;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .party-name {
            margin: 0 0 3px 0;
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        .muted {
            color: #5f6b7d;
        }

        .strong {
            font-weight: bold;
            color: #111827;
        }

        .party-row {
            margin: 1px 0;
        }

        .meta-table td {
            width: 50%;
            padding: 7px 9px;
            border-right: 1px solid #cfd6e2;
            border-bottom: 1px solid #cfd6e2;
        }

        .meta-table tr:last-child td {
            border-bottom: 0;
        }

        .meta-table td:nth-child(2) {
            border-right: 0;
        }

        .meta-label {
            display: block;
            margin-bottom: 2px;
            color: #6b7280;
            font-size: 8px;
        }

        .meta-value {
            display: block;
            color: #111827;
            font-size: 10px;
            font-weight: bold;
        }

        .items-table {
            table-layout: fixed;
        }

        .items-table thead th {
            padding: 8px 6px;
            border-right: 1px solid #cfd6e2;
            border-bottom: 1px solid #cfd6e2;
            background: #eef2f7;
            color: #27364c;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .items-table thead th:last-child,
        .items-table tbody td:last-child {
            border-right: 0;
        }

        .items-table tbody td {
            padding: 9px 6px;
            border-right: 1px solid #d9dee8;
            border-bottom: 1px solid #e4e8ef;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #cfd6e2;
        }

        .col-no {
            width: 6%;
            text-align: center;
        }

        .col-description {
            width: 46%;
        }

        .col-hsn {
            width: 12%;
            text-align: center;
        }

        .col-qty {
            width: 11%;
            text-align: right;
        }

        .col-rate {
            width: 12%;
            text-align: right;
        }

        .col-amount {
            width: 13%;
            text-align: right;
        }

        .item-name {
            margin-bottom: 2px;
            font-size: 10px;
            font-weight: bold;
            color: #111827;
        }

        .item-note {
            color: #6b7280;
            font-size: 8px;
        }

        .summary-wrapper td {
            border-bottom: 1px solid #cfd6e2;
        }

        .words-cell {
            width: 59%;
            padding: 12px 14px;
            border-right: 1px solid #cfd6e2;
        }

        .summary-cell {
            width: 41%;
            padding: 0;
        }

        .words-label {
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .words-value {
            font-size: 10px;
            font-weight: bold;
            color: #111827;
        }

        .summary-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #d9dee8;
        }

        .summary-table tr:last-child td {
            border-bottom: 0;
        }

        .summary-label {
            color: #4b5563;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .grand-total td {
            padding-top: 10px;
            padding-bottom: 10px;
            background: #14213d;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
        }

        .bottom-table td {
            border-right: 1px solid #cfd6e2;
        }

        .bottom-table td:last-child {
            border-right: 0;
        }

        .notes-cell {
            width: 60%;
            min-height: 125px;
            padding: 12px 14px;
        }

        .sign-cell {
            width: 40%;
            min-height: 125px;
            padding: 12px 14px;
            text-align: right;
        }

        .block-title {
            margin: 0 0 6px 0;
            color: #314158;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .declaration {
            margin-top: 10px;
            color: #5f6b7d;
            font-size: 8px;
        }

        .signature-space {
            height: 56px;
        }

        .signature-company {
            font-weight: bold;
            color: #111827;
        }

        .signature-label {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px solid #9ca3af;
            color: #4b5563;
            font-size: 8px;
        }

        .footer-note {
            padding-top: 7px;
            text-align: center;
            color: #7a8494;
            font-size: 8px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@php
    /*
     |--------------------------------------------------------------------------
     | Seller/company information
     |--------------------------------------------------------------------------
     | Put these values in config/invoice.php, or replace the fallbacks below.
     */
    $seller = [
        'name'       => config('invoice.company_name', 'Your Company Name'),
        'tagline'    => config('invoice.tagline', 'Quality products and professional service'),
        'address'    => config('invoice.address', 'Company address'),
        'city'       => config('invoice.city', ''),
        'state'      => config('invoice.state', ''),
        'postcode'   => config('invoice.postcode', ''),
        'gst'        => config('invoice.gst_number', ''),
        'pan'        => config('invoice.pan_number', ''),
        'email'      => config('invoice.email', ''),
        'phone'      => config('invoice.phone', ''),
        'bank_name'  => config('invoice.bank_name', ''),
        'account_no' => config('invoice.account_no', ''),
        'ifsc'       => config('invoice.ifsc', ''),
        'branch'     => config('invoice.branch', ''),
    ];

    $customer = $invoice->customer;
    $quotation = $invoice->quotation;
    $items = $quotation?->items ?? collect();

    $invoiceDate = $invoice->invoice_date
        ?? $invoice->date
        ?? $invoice->created_at;

    $formatDate = function ($date) {
        if (! $date) return '-';
        try {
            return \Carbon\Carbon::parse($date)->format('d-M-Y');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    };

    $money = function ($value) {
        return number_format((float) ($value ?? 0), 2, '.', ',');
    };

    $customerName = data_get($customer, 'company_name')
        ?? data_get($customer, 'customer_company_name')
        ?? data_get($customer, 'name')
        ?? data_get($customer, 'customer_name')
        ?? '-';

    $customerAddressParts = array_filter([
        data_get($customer, 'address'),
        data_get($customer, 'address_line_2'),
        data_get($customer, 'city.name') ?? data_get($customer, 'city'),
        data_get($customer, 'state.name') ?? data_get($customer, 'state'),
        data_get($customer, 'postcode') ?? data_get($customer, 'pincode'),
        data_get($customer, 'country.name') ?? data_get($customer, 'country'),
    ]);

    $customerAddress = implode(', ', $customerAddressParts);
    $customerGst = data_get($customer, 'gst_number')
        ?? data_get($customer, 'gst_no')
        ?? data_get($customer, 'gstin');

    $customerPhone = data_get($customer, 'phone')
        ?? data_get($customer, 'mobile')
        ?? data_get($customer, 'mobile_number');

    $customerEmail = data_get($customer, 'email');

    $subtotal = (float) (
        $invoice->subtotal
        ?? $invoice->sub_total
        ?? $items->sum(function ($item) {
            $qty = (float) (data_get($item, 'quantity') ?? data_get($item, 'qty') ?? 0);
            $rate = (float) (data_get($item, 'rate') ?? data_get($item, 'price') ?? data_get($item, 'unit_price') ?? 0);
            return data_get($item, 'amount') ?? data_get($item, 'total') ?? ($qty * $rate);
        })
    );

    $discount = (float) ($invoice->discount_amount ?? $invoice->discount ?? 0);
    $taxRate = (float) ($invoice->tax_rate ?? $invoice->gst_rate ?? 18);
    $taxableAmount = max(0, $subtotal - $discount);
    $taxAmount = (float) (
        $invoice->tax_amount
        ?? $invoice->gst_amount
        ?? round(($taxableAmount * $taxRate) / 100, 2)
    );

    $shipping = (float) ($invoice->shipping_amount ?? $invoice->shipping ?? 0);
    $roundOff = (float) ($invoice->round_off ?? 0);

    $grandTotal = (float) (
        $invoice->grand_total
        ?? $invoice->total_amount
        ?? $invoice->total
        ?? ($taxableAmount + $taxAmount + $shipping + $roundOff)
    );

    $taxType = strtoupper($invoice->tax_type ?? 'IGST');

    $quantityTotal = $items->sum(function ($item) {
        return (float) (data_get($item, 'quantity') ?? data_get($item, 'qty') ?? 0);
    });

    $amountInWords = $invoice->amount_in_words ?? null;
    if (! $amountInWords && class_exists(\NumberFormatter::class)) {
        try {
            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            $rupees = (int) floor($grandTotal);
            $paise = (int) round(($grandTotal - $rupees) * 100);
            $amountInWords = ucfirst($formatter->format($rupees)) . ' rupees';
            if ($paise > 0) {
                $amountInWords .= ' and ' . $formatter->format($paise) . ' paise';
            }
            $amountInWords .= ' only';
        } catch (\Throwable $e) {
            $amountInWords = null;
        }
    }

    $amountInWords = $amountInWords ?: ('Rupees ' . $money($grandTotal) . ' only');

    $referenceNumber = $invoice->reference_number
        ?? $quotation?->quotation_number
        ?? $quotation?->reference_number
        ?? '-';

    $paymentTerms = $invoice->payment_terms
        ?? $quotation?->payment_terms
        ?? 'As agreed';

    $deliveryTerms = $invoice->delivery_terms
        ?? $quotation?->delivery_terms
        ?? '-';

    $placeOfSupply = $invoice->place_of_supply
        ?? data_get($customer, 'state.name')
        ?? data_get($customer, 'state')
        ?? '-';
@endphp

<div class="invoice-shell">
    <table class="top-band">
        <tr>
            <td class="logo-cell">
                <div class="brand-name">{{ $seller['name'] }}</div>
                <div class="brand-subtitle">{{ $seller['tagline'] }}</div>
            </td>
            <td class="title-cell">
                <div class="invoice-title">TAX INVOICE</div>
                <div class="invoice-copy">Original for recipient</div>
            </td>
        </tr>
    </table>

    <table class="section-table">
        <tr>
            <td class="party-cell">
                <span class="section-label">Seller Details</span>
                <div class="party-name">{{ $seller['name'] }}</div>
                <div class="party-row">{{ $seller['address'] }}</div>
                @if($seller['city'] || $seller['state'] || $seller['postcode'])
                    <div class="party-row">
                        {{ implode(', ', array_filter([$seller['city'], $seller['state'], $seller['postcode']])) }}
                    </div>
                @endif
                @if($seller['gst'])
                    <div class="party-row"><span class="muted">GSTIN:</span> <span class="strong">{{ $seller['gst'] }}</span></div>
                @endif
                @if($seller['pan'])
                    <div class="party-row"><span class="muted">PAN:</span> <span class="strong">{{ $seller['pan'] }}</span></div>
                @endif
                @if($seller['email'] || $seller['phone'])
                    <div class="party-row">
                        @if($seller['email']) <span class="muted">Email:</span> {{ $seller['email'] }} @endif
                        @if($seller['email'] && $seller['phone']) &nbsp;|&nbsp; @endif
                        @if($seller['phone']) <span class="muted">Phone:</span> {{ $seller['phone'] }} @endif
                    </div>
                @endif

                <div style="height: 10px;"></div>

                <span class="section-label">Bill To</span>
                <div class="party-name">{{ $customerName }}</div>
                @if($customerAddress)
                    <div class="party-row">{{ $customerAddress }}</div>
                @endif
                @if($customerGst)
                    <div class="party-row"><span class="muted">GSTIN:</span> <span class="strong">{{ $customerGst }}</span></div>
                @endif
                @if($customerEmail || $customerPhone)
                    <div class="party-row">
                        @if($customerEmail) <span class="muted">Email:</span> {{ $customerEmail }} @endif
                        @if($customerEmail && $customerPhone) &nbsp;|&nbsp; @endif
                        @if($customerPhone) <span class="muted">Phone:</span> {{ $customerPhone }} @endif
                    </div>
                @endif
            </td>

            <td class="meta-cell">
                <table class="meta-table">
                    <tr>
                        <td>
                            <span class="meta-label">Invoice Number</span>
                            <span class="meta-value">{{ $invoice->invoice_number ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="meta-label">Invoice Date</span>
                            <span class="meta-value">{{ $formatDate($invoiceDate) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="meta-label">Reference Number</span>
                            <span class="meta-value">{{ $referenceNumber }}</span>
                        </td>
                        <td>
                            <span class="meta-label">Place of Supply</span>
                            <span class="meta-value">{{ $placeOfSupply }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="meta-label">Payment Terms</span>
                            <span class="meta-value">{{ $paymentTerms }}</span>
                        </td>
                        <td>
                            <span class="meta-label">Due Date</span>
                            <span class="meta-value">{{ $formatDate($invoice->due_date ?? null) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="meta-label">Delivery Note</span>
                            <span class="meta-value">{{ $invoice->delivery_note ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="meta-label">Delivery Terms</span>
                            <span class="meta-value">{{ $deliveryTerms }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="meta-label">PO Number</span>
                            <span class="meta-value">{{ $invoice->po_number ?? $quotation?->po_number ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="meta-label">PO Date</span>
                            <span class="meta-value">{{ $formatDate($invoice->po_date ?? $quotation?->po_date ?? null) }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">#</th>
                <th class="col-description">Description of Goods / Services</th>
                <th class="col-hsn">HSN/SAC</th>
                <th class="col-qty">Quantity</th>
                <th class="col-rate">Rate</th>
                <th class="col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                @php
                    $product = $item->product ?? null;
                    $name = data_get($product, 'name')
                        ?? data_get($product, 'product_name')
                        ?? data_get($item, 'name')
                        ?? data_get($item, 'description')
                        ?? 'Item';
                    $description = data_get($item, 'description')
                        ?? data_get($product, 'description');
                    $hsn = data_get($item, 'hsn_code')
                        ?? data_get($product, 'hsn_code')
                        ?? data_get($product, 'hsn')
                        ?? '-';
                    $qty = (float) (data_get($item, 'quantity') ?? data_get($item, 'qty') ?? 0);
                    $unit = data_get($item, 'unit') ?? data_get($product, 'unit') ?? 'Pcs';
                    $rate = (float) (data_get($item, 'rate') ?? data_get($item, 'price') ?? data_get($item, 'unit_price') ?? 0);
                    $lineAmount = (float) (data_get($item, 'amount') ?? data_get($item, 'total') ?? ($qty * $rate));
                @endphp
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-description">
                        <div class="item-name">{{ $name }}</div>
                        @if($description && $description !== $name)
                            <div class="item-note">{{ strip_tags($description) }}</div>
                        @endif
                    </td>
                    <td class="col-hsn">{{ $hsn }}</td>
                    <td class="col-qty"><span class="strong">{{ rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') }}</span> {{ $unit }}</td>
                    <td class="col-rate">{{ $money($rate) }}</td>
                    <td class="col-amount"><span class="strong">{{ $money($lineAmount) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td class="col-no">1</td>
                    <td class="col-description">
                        <div class="item-name">No invoice items found</div>
                        <div class="item-note">Check the quotation.items relationship and item field names.</div>
                    </td>
                    <td class="col-hsn">-</td>
                    <td class="col-qty">0</td>
                    <td class="col-rate">0.00</td>
                    <td class="col-amount">0.00</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-wrapper">
        <tr>
            <td class="words-cell">
                <div class="words-label">Amount chargeable in words</div>
                <div class="words-value">{{ $amountInWords }}</div>
                @if($quantityTotal > 0)
                    <div style="margin-top: 8px;" class="muted">
                        Total Quantity: <span class="strong">{{ rtrim(rtrim(number_format($quantityTotal, 2, '.', ''), '0'), '.') }}</span>
                    </div>
                @endif
            </td>
            <td class="summary-cell">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Subtotal</td>
                        <td class="summary-value">{{ $money($subtotal) }}</td>
                    </tr>
                    @if($discount > 0)
                        <tr>
                            <td class="summary-label">Discount</td>
                            <td class="summary-value">- {{ $money($discount) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="summary-label">{{ $taxType }} @ {{ rtrim(rtrim(number_format($taxRate, 2, '.', ''), '0'), '.') }}%</td>
                        <td class="summary-value">{{ $money($taxAmount) }}</td>
                    </tr>
                    @if($shipping != 0)
                        <tr>
                            <td class="summary-label">Shipping / Other Charges</td>
                            <td class="summary-value">{{ $money($shipping) }}</td>
                        </tr>
                    @endif
                    @if($roundOff != 0)
                        <tr>
                            <td class="summary-label">Round Off</td>
                            <td class="summary-value">{{ $money($roundOff) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>Grand Total</td>
                        <td style="text-align: right;">&#8377; {{ $money($grandTotal) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="bottom-table">
        <tr>
            <td class="notes-cell">
                @if($seller['bank_name'] || $seller['account_no'] || $seller['ifsc'])
                    <div class="block-title">Bank Details</div>
                    @if($seller['bank_name']) <div><span class="muted">Bank:</span> <span class="strong">{{ $seller['bank_name'] }}</span></div> @endif
                    @if($seller['account_no']) <div><span class="muted">A/C No.:</span> <span class="strong">{{ $seller['account_no'] }}</span></div> @endif
                    @if($seller['ifsc']) <div><span class="muted">IFSC:</span> <span class="strong">{{ $seller['ifsc'] }}</span></div> @endif
                    @if($seller['branch']) <div><span class="muted">Branch:</span> {{ $seller['branch'] }}</div> @endif
                @endif

                @if($invoice->remarks ?? null)
                    <div style="height: 9px;"></div>
                    <div class="block-title">Remarks</div>
                    <div>{{ $invoice->remarks }}</div>
                @endif

                <div class="declaration">
                    <strong>Declaration:</strong><br>
                    We declare that this invoice shows the actual price of the goods/services described and that all particulars are true and correct.
                </div>
            </td>
            <td class="sign-cell">
                <div class="signature-company">For {{ $seller['name'] }}</div>
                <div class="signature-space"></div>
                <div class="signature-label">Authorised Signatory</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer-note">
    This is a computer-generated invoice and does not require a physical signature.
</div>
</body>
</html>
