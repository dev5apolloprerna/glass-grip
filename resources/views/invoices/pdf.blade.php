<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>{!! file_get_contents(public_path('css/invoice-pdf.css')) !!}</style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div class="left">
                <div class="company-name">{{ config('app.name', 'Your Company') }}</div>
            </div>
            <div class="right">
                <div class="invoice-title">INVOICE</div>
                <div>{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <table class="meta-table">
            <tr>
                <td class="label">Bill To</td>
                <td>
                    <strong>{{ $invoice->customer->name }}</strong><br>
                    @if($invoice->customer->address){{ $invoice->customer->address }}<br>@endif @if($invoice->customer->address_line_2){{ $invoice->customer->address_line_2 }}<br>@endif {{ $invoice->customer->city }}, {{ $invoice->customer->state }} - {{ $invoice->customer->pincode }}<br>
                    @if($invoice->customer->phone)Phone: {{ $invoice->customer->phone }}<br>@endif
                    @if($invoice->customer->gst_number)GSTIN: {{ $invoice->customer->gst_number }}@endif
                </td>
                <td class="label">Invoice Date</td>
                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label"></td>
                <td></td>
                <td class="label">Quotation No.</td>
                <td>{{ $invoice->quotation->quotation_number }}</td>
            </tr>
        </table>

        <div class="section-title">Items</div>
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product / Description</th><th>HSN</th>
                    <th class="text-right">Size (Mtr)</th>
                    <th class="text-right">No. of Rolls</th>
                    <th class="text-right">Total Mtr</th>
                    <th class="text-right">Price/Mtr</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->quotation->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->product->name }}<br><small>{{ $item->product->description }}</small></td><td>{{ $item->product->hsn_code }}</td>
                        <td class="text-right">{{ number_format($item->size_mtr, 2) }}</td>
                        <td class="text-right">{{ $item->no_of_rolls }}</td>
                        <td class="text-right">{{ number_format($item->total_mtr, 2) }}</td>
                        <td class="text-right">{{ number_format($item->price_per_mtr, 2) }}</td>
                        <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Sub Total</td><td class="text-right">Rs. {{ number_format($invoice->sub_total, 2) }}</td></tr>
            @if($invoice->discount_amount > 0)
                <tr><td>Discount</td><td class="text-right">- Rs. {{ number_format($invoice->discount_amount, 2) }}</td></tr>
                <tr><td>Total Amount</td><td class="text-right">Rs. {{ number_format($invoice->sub_total - $invoice->discount_amount, 2) }}</td></tr>
            @endif
            @if($invoice->gst_amount > 0)
                @if($invoice->cgst_amount > 0)<tr><td>CGST (9%)</td><td class="text-right">Rs. {{ number_format($invoice->cgst_amount,2) }}</td></tr><tr><td>SGST (9%)</td><td class="text-right">Rs. {{ number_format($invoice->sgst_amount,2) }}</td></tr>@else<tr><td>IGST (18%)</td><td class="text-right">Rs. {{ number_format($invoice->igst_amount,2) }}</td></tr>@endif
            @endif
            @if($invoice->round_off != 0)
                <tr><td>Round Off</td><td class="text-right">{{ $invoice->round_off > 0 ? '+' : '' }} Rs. {{ number_format($invoice->round_off, 2) }}</td></tr>
            @endif
            <tr class="grand"><td>Net Amount</td><td class="text-right">Rs. {{ number_format($invoice->total_amount, 2) }}</td></tr>
        </table>

        <div class="footer-note">
            This is a system-generated invoice created from approved quotation {{ $invoice->quotation->quotation_number }} by {{ $invoice->quotation->user->name }}.
        </div>
    </div>
</body>
</html>
