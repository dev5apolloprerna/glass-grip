<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice {{ $invoice->invoice_number }}</title>
    <style>{!! file_get_contents(public_path('css/invoice-pdf.css')) !!}</style>
</head>
<body>
   <div class="invoice-box tax-invoice">
    <div class="tax-heading">TAX INVOICE</div>

       <table class="document-header">
        <tr>
            <td class="seller" rowspan="3">
                <div class="company-name">{{ config('app.name', 'Your Company') }}</div>
                <div class="company-tagline">Quality products. Reliable service.</div>
            </td>
            <td class="header-label">Invoice No.</td>
            <td><strong>{{ $invoice->invoice_number }}</strong></td>
        </tr>
        <tr><td class="header-label">Dated</td><td>{{ $invoice->invoice_date->format('d M Y') }}</td></tr>
        <tr><td class="header-label">Quotation No.</td><td>{{ $invoice->quotation->quotation_number }}</td></tr>
    </table>

    <table class="party-table">
        <tr>
            <td>
                <div class="block-label">Buyer (Bill To)</div>
                <strong>{{ $invoice->customer->name }}</strong><br>
                {{ collect([$invoice->customer->address, $invoice->customer->address_line_2])->filter()->implode(', ') }}<br>
                {{ $invoice->customer->city }}, {{ $invoice->customer->state }} - {{ $invoice->customer->pincode }}
                @if($invoice->customer->phone)<br>Phone: {{ $invoice->customer->phone }}@endif
                @if($invoice->customer->gst_number)<br><strong>GSTIN/UIN: {{ $invoice->customer->gst_number }}</strong>@endif
            </td>
            <td>
                <div class="block-label">Consignee (Ship To)</div>
                <strong>{{ $invoice->customer->name }}</strong><br>
                {{ collect([$invoice->shipping_address, $invoice->shipping_address_line_2])->filter()->implode(', ') }}<br>
                {{ $invoice->shipping_city }}, {{ $invoice->shipping_state }} - {{ $invoice->shipping_pincode }}
            </td>
        </tr>
    </table>

    <table class="items invoice-items">
        <thead>
            <tr>
 <th class="text-center serial">Sl No.</th>
                <th>Description of Goods</th>
                <th class="text-center">HSN/SAC</th>
                <th class="text-right">Size (Mtr)</th>
                <th class="text-right">Rolls</th>
                <th class="text-right">Total Mtr</th>
                <th class="text-right">Rate/Mtr</th>
                <th class="text-right">Amount</th>
            </tr>
               </thead>
        <tbody>
            @foreach($invoice->quotation->items as $i => $item)
                <tr>
                   <td class="text-center">{{ $i + 1 }}</td>
                    <td><strong>{{ $item->product->name }}</strong>@if($item->product->description)<br><small>{{ $item->product->description }}</small>@endif</td>
                    <td class="text-center">{{ $item->product->hsn_code }}</td>
                    <td class="text-right">{{ number_format($item->size_mtr, 2) }}</td>
                    <td class="text-right">{{ $item->no_of_rolls }}</td>
                    <td class="text-right">{{ number_format($item->total_mtr, 2) }}</td>
                    <td class="text-right">{{ number_format($item->price_per_mtr, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="item-space"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="tax-summary">
            <tr><td>Sub Total</td><td class="text-right">Rs. {{ number_format($invoice->sub_total, 2) }}</td></tr>
 @if($invoice->discount_amount > 0)<tr><td>Less: Discount</td><td class="text-right">Rs. {{ number_format($invoice->discount_amount, 2) }}</td></tr>@endif
            @if($invoice->cgst_amount > 0)
                <tr><td>CGST @ 9%</td><td class="text-right">Rs. {{ number_format($invoice->cgst_amount, 2) }}</td></tr>
                <tr><td>SGST @ 9%</td><td class="text-right">Rs. {{ number_format($invoice->sgst_amount, 2) }}</td></tr>
            @elseif($invoice->igst_amount > 0)
                <tr><td>IGST @ 18%</td><td class="text-right">Rs. {{ number_format($invoice->igst_amount, 2) }}</td></tr>
            @endif
            @if($invoice->round_off != 0)<tr><td>Round Off</td><td class="text-right">{{ $invoice->round_off > 0 ? '+' : '' }} Rs. {{ number_format($invoice->round_off, 2) }}</td></tr>@endif
            <tr class="grand"><td>Total</td><td class="text-right">Rs. {{ number_format($invoice->total_amount, 2) }}</td></tr>
        </table>

    <table class="amount-words"><tr><td><span>Amount Chargeable (in words)</span><br><strong>Indian Rupees {{ number_format($invoice->total_amount, 2) }} Only</strong></td><td class="text-right">E. &amp; O.E.</td></tr></table>

    <table class="declaration-signature">
        <tr>
            <td><strong>Declaration</strong><br>We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</td>
            <td class="signature"><strong>for {{ config('app.name') }}</strong><div class="signature-space"></div>Authorised Signatory</td>
        </tr>
    </table>
    <div class="bottom-bar">This is a computer-generated Tax Invoice</div>
</div>
    </div>
</body>
</html>
