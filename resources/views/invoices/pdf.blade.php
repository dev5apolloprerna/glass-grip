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
                    @if($invoice->customer->address){{ $invoice->customer->address }}<br>@endif
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
                    <th>Product</th>
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
                        <td>{{ $item->product->name }}</td>
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
            @if($invoice->gst_amount > 0)
                <tr><td>GST (18%)</td><td class="text-right">Rs. {{ number_format($invoice->gst_amount, 2) }}</td></tr>
            @endif
            <tr class="grand"><td>Total</td><td class="text-right">Rs. {{ number_format($invoice->total_amount, 2) }}</td></tr>
        </table>

        <div class="footer-note">
            This is a system-generated invoice created from approved quotation {{ $invoice->quotation->quotation_number }} by {{ $invoice->quotation->user->name }}.
        </div>
    </div>
</body>
</html>
