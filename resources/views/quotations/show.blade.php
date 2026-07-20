@extends('layouts.app')

@section('title', 'Quotation ' . $quotation->quotation_number)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>{{ $quotation->quotation_number }} <span class="pill pill-{{ $quotation->status }}">{{ $quotation->status }}</span></h3>
            <div>
                @if($quotation->isEditable())
                    <a href="{{ route('quotations.edit', $quotation) }}" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="{{ route('quotations.approve', $quotation) }}" style="display:inline;" data-confirm="Approve this quotation? Once approved it cannot be edited and an invoice will be generated.">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">Approve &amp; Generate Invoice</button>
                    </form>
                    <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" style="display:inline;" data-confirm="Delete this quotation?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                @else
                    <a href="{{ route('invoices.show', $quotation->invoice) }}" class="btn btn-primary btn-sm">View Invoice</a>
                    <a href="{{ route('invoices.download', $quotation->invoice) }}" class="btn btn-secondary btn-sm">Download PDF</a>
                @endif
                <a href="{{ route('quotations.index') }}" class="btn btn-secondary btn-sm">&larr; Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div>
                    <p class="text-muted mb-0">Customer</p>
                    <p><a href="{{ route('customers.show', $quotation->customer) }}">{{ $quotation->customer->name }}</a></p>
                </div>
                <div>
                    <p class="text-muted mb-0">Quotation Date</p>
                    <p>{{ $quotation->quotation_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-muted mb-0">Created By</p>
                    <p>{{ $quotation->user->name }}</p>
                </div>
                <div>
                    <p class="text-muted mb-0">GST</p>
                    <p>{{ $quotation->gst_applicable ? 'Applicable (18%)' : 'Not Applicable' }}</p>
                </div>
            </div>
            @if($quotation->status === 'approved')
                <div class="form-row">
                    <div>
                        <p class="text-muted mb-0">Approved By</p>
                        <p>{{ $quotation->approvedBy->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-muted mb-0">Approved At</p>
                        <p>{{ $quotation->approved_at->format('d M Y h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-muted mb-0">Invoice Number</p>
                        <p>{{ $quotation->invoice->invoice_number ?? '-' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Products</h3></div>
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
                    @foreach($quotation->items as $item)
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
                <div class="row"><span>Sub Total</span><span>&#8377;{{ number_format($quotation->sub_total, 2) }}</span></div>
                @if($quotation->gst_applicable)
                    <div class="row"><span>GST (18%)</span><span>&#8377;{{ number_format($quotation->gst_amount, 2) }}</span></div>
                @endif
                <div class="row grand"><span>Total</span><span>&#8377;{{ number_format($quotation->total_amount, 2) }}</span></div>
            </div>
        </div>
    </div>
@endsection
