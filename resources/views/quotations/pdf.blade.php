<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>{{ $quotation->quotation_number }}</title>
		<style>{!! file_get_contents(public_path('css/invoice-pdf.css')) !!}</style>
</head>

<body>
	<div class="invoice-box">
		<div class="invoice-header">
			<div class="left">
				<img class="company-logo" src="{{ public_path('images/glass-grip-logo.png') }}" alt="Glass Grip">
				<div class="company-name">{{ config('invoice.company_name') }}</div><div class="company-tagline">Quality products. Reliable service.</div>
				<div class="company-address">{{ config('invoice.address') }},<br>{{ config('invoice.city') }}, {{ config('invoice.state') }} - {{ config('invoice.postcode') }}.</div>
			</div>
			<div class="right">
				<div class="invoice-title">QUOTATION</div>
				<div class="document-number">{{ $quotation->quotation_number }}</div>
			</div>
		</div>
		<table class="meta-table">
			<tr>
				<td class="label">Bill To</td>
				<td><strong>{{ $quotation->customer->name }}</strong><br>{{ $quotation->customer->address }}<br>{{
					$quotation->customer->address_line_2 }}<br>{{ $quotation->customer->city }}, {{
					$quotation->customer->state }} - {{ $quotation->customer->pincode }}</td>
				<td class="label">Ship To</td>
				<td>{{ $quotation->shipping_address }}<br>{{ $quotation->shipping_address_line_2 }}<br>{{
					$quotation->shipping_city }}, {{ $quotation->shipping_state }} - {{ $quotation->shipping_pincode }}
				</td>
			</tr>
			<tr>
				<td class="label">Date</td>
				<td>{{ $quotation->quotation_date->format('d M Y') }}</td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<table class="items">
			<thead>
				<tr>
					<th>#</th>
					<th>Product / Description</th>
					<th>HSN</th>
					<th>Size</th>
					<th>Rolls</th>
					<th>Total Mtr</th>
					<th>Rate</th>
					<th>Amount</th>
				</tr>
			</thead>
			<tbody>@foreach($quotation->items as $i=>$item)<tr>
					<td>{{ $i+1 }}</td>
					<td>{{ $item->product->name }}<br><small>{{ $item->product->description }}</small></td>
					<td>{{ $item->product->hsn_code }}</td>
					<td>{{ number_format($item->size_mtr,2) }}</td>
					<td>{{ $item->no_of_rolls }}</td>
					<td>{{ number_format($item->total_mtr,2) }}</td>
					<td>{{ number_format($item->price_per_mtr,2) }}</td>
					<td>{{ number_format($item->amount,2) }}</td>
				</tr>@endforeach</tbody>
		</table>
		<table class="totals">
			<tr>
				<td>Sub Total</td>
				<td class="text-right">Rs. {{ number_format($quotation->sub_total,2) }}</td>
			</tr>@if($quotation->discount_amount>0)<tr>
				<td>Discount</td>
				<td class="text-right">- Rs. {{ number_format($quotation->discount_amount,2) }}</td>
			</tr>@endif @if($quotation->cgst_amount>0)<tr>
				<td>CGST (9%)</td>
				<td class="text-right">Rs. {{ number_format($quotation->cgst_amount,2) }}</td>
			</tr>
			<tr>
				<td>SGST (9%)</td>
				<td class="text-right">Rs. {{ number_format($quotation->sgst_amount,2) }}</td>
			</tr>@elseif($quotation->igst_amount>0)<tr>
				<td>IGST (18%)</td>
				<td class="text-right">Rs. {{ number_format($quotation->igst_amount,2) }}</td>
			</tr>@endif<tr class="grand">
				<td>Net Amount</td>
				<td class="text-right">Rs. {{ number_format($quotation->total_amount,2) }}</td>
			</tr>
		</table>
		        <div class="footer-grid"><div class="footer-note">Please retain this document for your records.<br>This is a computer-generated document.</div><div class="signature"><div class="signature-space"></div><strong>Authorised Signatory</strong><br>For {{ config('invoice.company_name') }}</div></div>
        <div class="bottom-bar">Thank you for your business</div>
	</div>
</body>

</html>