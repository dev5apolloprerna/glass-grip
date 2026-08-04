<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>{{ $deliveryChallan->challan_number }}</title>
	<style>{!! file_get_contents(public_path('css/invoice-pdf.css')) !!}</style>
</head>

<body>
	<div class="invoice-box">@php($invoice=$deliveryChallan->invoice)
		<div class="invoice-header">
			<div class="left">
			<img class="company-logo" src="{{ public_path('images/glass-grip-logo.png') }}" alt="Glass Grip">
				<div class="company-name">{{ config('invoice.company_name') }}</div><div class="company-tagline">Quality products. Reliable service.</div>
				<div class="company-address">{{ config('invoice.address') }},<br>{{ config('invoice.city') }}, {{ config('invoice.state') }} - {{ config('invoice.postcode') }}.</div>
			</div>
			<div class="right">
				<div class="invoice-title">DELIVERY CHALLAN</div><div class="document-number">{{ $deliveryChallan->challan_number }}</div>
			</div>
		</div>
		<table class="meta-table">
			<tr>
				<td class="label">Deliver To</td>
				<td><strong>{{ $invoice->customer->name }}</strong><br>{{ $invoice->shipping_address }}<br>{{
					$invoice->shipping_address_line_2 }}<br>{{ $invoice->shipping_city }}, {{ $invoice->shipping_state
					}} - {{ $invoice->shipping_pincode }}</td>
				<td class="label">Date</td>
				<td>{{ $deliveryChallan->challan_date->format('d M Y') }}<br>Invoice: {{ $invoice->invoice_number }}
				</td>
			</tr>
		</table>
		<table class="items">
			<thead>
				<tr>
					<th>#</th>
					<th>Product / Description</th>
					<th>HSN</th>
					<th>Size (Mtr)</th>
					<th>Rolls</th>
					<th>Total Mtr</th>
				</tr>
			</thead>
			<tbody>@foreach($invoice->quotation->items as $i=>$item)<tr>
					<td>{{ $i+1 }}</td>
					<td>{{ $item->product->name }}<br><small>{{ $item->product->description }}</small></td>
					<td>{{ $item->product->hsn_code }}</td>
					<td>{{ number_format($item->size_mtr,2) }}</td>
					<td>{{ $item->no_of_rolls }}</td>
					<td>{{ number_format($item->total_mtr,2) }}</td>
				</tr>@endforeach</tbody>
		</table>
		        <div class="footer-grid"><div class="footer-note">Please retain this document for your records.<br>This is a computer-generated document.</div><div class="signature"><div class="signature-space"></div><strong>Authorised Signatory</strong><br>For {{ config('invoice.company_name') }}</div></div>
        <div class="bottom-bar">Thank you for your business</div>
	</div>
</body>

</html>