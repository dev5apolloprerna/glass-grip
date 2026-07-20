@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>Invoice {{ $invoice->invoice_number }}</h3>
            <div>
                <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-primary btn-sm">Download PDF</a>
                <a href="{{ route('quotations.show', $invoice->quotation) }}" class="btn btn-secondary btn-sm">&larr; Back to Quotation</a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div>
                    <p class="text-muted mb-0">Customer</p>
                    <p>{{ $invoice->customer->name }}</p>
                </div>
                <div>
                    <p class="text-muted mb-0">Invoice Date</p>
                    <p>{{ $invoice->invoice_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-muted mb-0">Quotation No.</p>
                    <p>{{ $invoice->quotation->quotation_number }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Items</h3></div>
        <div class="card-body table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Size (Mtr)</th>
                        <th class="text-right"># Rolls</th>
                        <th class="text-right">Total Mtr</th>
                        <th class="text-right">Price/Mtr</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->quotation->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td class="text-right">{{ number_format($item->size_mtr, 2) }}</td>
                            <td class="text-right">{{ $item->no_of_rolls }}</td>
                            <td class="text-right">{{ number_format($item->total_mtr, 2) }}</td>
                            <td class="text-right">&#8377;{{ number_format($item->price_per_mtr, 2) }}</td>
                            <td class="text-right">&#8377;{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-box">
                <div class="row"><span>Sub Total</span><span>&#8377;{{ number_format($invoice->sub_total, 2) }}</span></div>
                @if($invoice->gst_amount > 0)
                    <div class="row"><span>GST (18%)</span><span>&#8377;{{ number_format($invoice->gst_amount, 2) }}</span></div>
                @endif
                <div class="row grand"><span>Total</span><span>&#8377;{{ number_format($invoice->total_amount, 2) }}</span></div>
            </div>
        </div>
    </div>
@endsection
