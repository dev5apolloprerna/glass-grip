<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>{{ $payment->receipt_number }}</title><style>
body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:12px;margin:0}.receipt{border:2px solid #1e3a8a;padding:24px}.header{border-bottom:2px solid #1e3a8a;padding-bottom:14px;margin-bottom:18px}.company{font-size:22px;font-weight:bold;color:#1e3a8a}.title{float:right;font-size:20px;font-weight:bold;color:#1e3a8a}.muted{color:#6b7280}.details{width:100%;border-collapse:collapse;margin:18px 0}.details td{padding:9px;border:1px solid #d1d5db}.label{width:34%;background:#f3f4f6;font-weight:bold}.amount{background:#eff6ff;border:1px solid #93c5fd;padding:14px;text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a}.footer{margin-top:35px;border-top:1px solid #d1d5db;padding-top:14px}.signature{text-align:right;margin-top:35px}
</style></head><body><div class="receipt">
    <div class="header"><span class="title">PAYMENT RECEIPT</span><div class="company">{{ config('invoice.company_name') }}</div><div class="muted">{{ config('invoice.address') }}, {{ config('invoice.city') }}, {{ config('invoice.state') }} - {{ config('invoice.postcode') }}</div></div>
    <table class="details">
        <tr><td class="label">Receipt Number</td><td><strong>{{ $payment->receipt_number }}</strong></td></tr>
        <tr><td class="label">Payment Date</td><td>{{ $payment->payment_date->format('d M Y') }}</td></tr>
        <tr><td class="label">Received From</td><td><strong>{{ $payment->customer->name }}</strong><br>{{ $payment->customer->contact_person }} {{ $payment->customer->phone ? '· '.$payment->customer->phone : '' }}</td></tr>
        <tr><td class="label">Payment Method</td><td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
        <tr><td class="label">Reference Number</td><td>{{ $payment->reference_number ?: '-' }}</td></tr>
        <tr><td class="label">Collected By</td><td>{{ $payment->employee->name ?? '-' }}</td></tr>
    </table>
    <div class="amount">Amount Received: &#8377;{{ number_format($payment->amount, 2) }}</div>
    @if($payment->notes)<p><strong>Notes:</strong> {{ $payment->notes }}</p>@endif
    <div class="signature">For {{ config('invoice.company_name') }}<br><br><br><strong>Authorised Signatory</strong></div>
    <div class="footer muted">This is a computer-generated customer-wise payment receipt and does not require a signature.</div>
</div></body></html>
