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

    {{-- Seller block + invoice meta --}}
    <table class="document-header">
        <colgroup>
            <col style="width:50%"><col style="width:12%"><col style="width:14%"><col style="width:10%"><col style="width:14%">
        </colgroup>
        <tr>
            <td class="seller" rowspan="3">
                <div class="company-name">{{ config('app.name', 'Your Company') }}</div>
                <div class="company-tagline">Quality products. Reliable service.</div>
            </td>
            <td class="header-label">Invoice No.</td>
            <td><strong>{{ $invoice->invoice_number }}</strong></td>
            <td class="header-label">Dated</td>
            <td><strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></td>
        </tr>
        <tr>
            <td class="header-label">Delivery Note</td>
            <td>{{ $invoice->delivery_note ?? '-' }}</td>
            <td class="header-label">Mode/Terms of Payment</td>
            <td>{{ $invoice->payment_terms ?? '-' }}</td>
        </tr>
        <tr>
            <td class="header-label">Reference No.</td>
            <td class="header-label">Other Reference(s)</td>
            <td>-</td>
            <td>{{ $invoice->other_reference ?? '-' }}</td>
        </tr>
    </table>

    {{-- Buyer block + despatch details (Buyer's Order No/Dated, Despatch Doc No/Dated, Despatched Through/Destination, Terms of Delivery) --}}
    <table class="document-header buyer-header">
        <colgroup>
            <col style="width:50%"><col style="width:12%"><col style="width:14%"><col style="width:10%"><col style="width:14%">
        </colgroup>
        <tr>
            <td class="seller" rowspan="4">
                <div class="block-label">Buyer</div>
                <strong>{{ $invoice->customer->name }}</strong><br>
                {{ collect([$invoice->customer->address, $invoice->customer->address_line_2])->filter()->implode(', ') }}<br>
                {{ $invoice->customer->city }}, {{ $invoice->customer->state }} - {{ $invoice->customer->pincode }}
                @if($invoice->customer->gst_number)<br><strong>GST No. {{ $invoice->customer->gst_number }}</strong>@endif
                @if($invoice->customer->phone)<br>Mob No. {{ $invoice->customer->phone }}@endif
            </td>
            <td class="header-label">Buyer's Order No.</td>
            <td>{{ $invoice->buyer_order_no ?? '-' }}</td>
            <td class="header-label">Dated</td>
            <td>{{ $invoice->buyer_order_date ?? '-' }}</td>
        </tr>
        <tr>
            <td class="header-label">Despatch Document No.</td>
            <td>{{ $invoice->despatch_doc_no ?? '-' }}</td>
            <td class="header-label">Dated</td>
            <td>{{ $invoice->despatch_doc_date ?? '-' }}</td>
        </tr>
        <tr>
            <td class="header-label">Despatched Through</td>
            <td>{{ $invoice->despatched_through ?? '-' }}</td>
            <td class="header-label">Destination</td>
            <td>{{ $invoice->shipping_city ?? '-' }}</td>
        </tr>
        <tr>
            <td class="header-label">Terms of Delivery</td>
            <td colspan="3">{{ $invoice->terms_of_delivery ?? '-' }}</td>
        </tr>
    </table>

    {{-- Items + subtotal / tax / total, all inside one continuous grid, exactly like the reference --}}
    <table class="items invoice-items">
        <colgroup>
            <col style="width:8%"><col style="width:48%"><col style="width:12%">
        </colgroup>
        <thead>
            <tr>
                <th class="text-center serial">Sr No.</th>
                <th>Description of Goods</th>
                <th class="text-right">No. of Rolls</th>
                <th class="text-right">Per Mtr Rate</th>
                <th class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->quotation->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->name }}</strong>
                        @if($item->product->description)<small>{{ $item->product->description }}</small>@endif
                        <small>HSN: {{ $item->product->hsn_code }} &nbsp;|&nbsp; Size: {{ number_format($item->size_mtr, 2) }} Mtr &nbsp;|&nbsp; Total Mtr: {{ number_format($item->total_mtr, 2) }} Mtr</small>
                    </td>
                    <td class="text-right">{{ $item->no_of_rolls }}</td>
                    <td class="text-right">{{ number_format($item->price_per_mtr, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach

            {{-- Sub Total --}}
            <tr class="summary-row">
                <td></td><td></td><td></td><td></td>
                <td class="text-right">{{ number_format($invoice->sub_total, 2) }}</td>
            </tr>

            @if($invoice->discount_amount > 0)
            <tr class="summary-row">
                <td></td><td>Less: Discount</td><td></td><td></td><td></td>
                <td class="text-right">{{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            @endif

            @if($invoice->cgst_amount > 0)
            <tr class="summary-row">
                <td></td><td>CGST @ 9%</td><td></td><td>9%</td>
                <td class="text-right">{{ number_format($invoice->cgst_amount, 2) }}</td>
            </tr>
            <tr class="summary-row">
                <td></td><td>CGST @ 9%</td><td></td><td>9%</td>
                <td class="text-right">{{ number_format($invoice->sgst_amount, 2) }}</td>
            </tr>
            @elseif($invoice->igst_amount > 0)
            <tr class="summary-row">
                <td></td><td>IGST @ 18%</td><td></td><td>18%</td>
                <td class="text-right">{{ number_format($invoice->igst_amount, 2) }}</td>
            </tr>
            @endif

            @if($invoice->round_off != 0)
            <tr class="summary-row">
                <td></td><td>Round Off</td><td></td><td></td>
                <td class="text-right">{{ $invoice->round_off > 0 ? '+' : '' }} {{ number_format($invoice->round_off, 2) }}</td>
            </tr>
            @endif

            {{-- Total --}}
            <tr class="total-row">
                <td colspan="2" class="text-right">Total</td>              
                 <td class="text-right">{{ $invoice->quotation->items->sum('no_of_rolls') }}</td>
                <td></td>
                <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="amount-words">
        <colgroup><col style="width:83%"><col style="width:17%"></colgroup>
        <tr>
            <td><span>Amount Chargeable (in words)</span><br><strong>Indian Rupees {{ number_format($invoice->total_amount, 2) }} Only</strong></td>
            <td class="text-right">E. &amp; O.E.</td>
        </tr>
    </table>

    <table class="declaration-signature">
        <colgroup><col style="width:65%"><col style="width:35%"></colgroup>
        <tr>
            <td><strong>Declaration</strong><br>We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</td>
            <td class="signature"><strong>for {{ config('app.name') }}</strong><div class="signature-space"></div>Authorised Signatory</td>
        </tr>
    </table>

    <div class="bottom-bar">This is a computer-generated Tax Invoice</div>
   </div>
</body>
</html>