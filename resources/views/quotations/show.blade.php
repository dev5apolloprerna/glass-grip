@extends('layouts.app')

@section('title', 'Quotation ' . $quotation->quotation_number)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>{{ $quotation->quotation_number }} <span class="pill pill-{{ $quotation->displayStatusClass() }}">{{ $quotation->displayStatus() }}</span></h3>
            <div>
                <a href="{{ route('quotations.download', $quotation) }}" class="btn btn-secondary btn-sm">Quotation PDF</a>
                @if($quotation->isEditable())
                    <form method="POST" action="{{ route('quotations.mark-sent', $quotation) }}" style="display:inline" data-confirm="Mark this quotation as sent? It can no longer be edited.">
                        @csrf
                        <button class="btn btn-success btn-sm">Send Quotation</button>
                    </form>
                @elseif($quotation->isSent())
                    <form method="POST" action="{{ route('quotations.approve', $quotation) }}" style="display:inline-flex; gap:6px; align-items:center;" data-confirm="Approve this quotation and create the invoice with the entered number?">
                        @csrf
                        <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}" placeholder="Enter invoice number" maxlength="255" required style="width:190px; padding:5px 10px;">
                        <button class="btn btn-success btn-sm">Approve &amp; Create Invoice</button>
                    </form>
                @endif
                @if($quotation->isEditable())
                    <a href="{{ route('quotations.edit', $quotation) }}" class="btn btn-secondary btn-sm">Edit</a>

                    <form method="POST" action="{{ route('quotations.reject', $quotation) }}" style="display:inline;" data-confirm="Reject this quotation?">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" style="display:inline;" data-confirm="Delete this quotation?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                @elseif($quotation->status === 'approved')
                    <a href="{{ route('invoices.show', $quotation->invoice) }}" class="btn btn-primary btn-sm">View Invoice</a>
                    <a href="{{ route('invoices.download', $quotation->invoice) }}" class="btn btn-secondary btn-sm">Download PDF</a>
                    <a href="{{ route('invoices.show', $quotation->invoice) }}#collect-payment" class="btn btn-success btn-sm">Collect Payment</a>
                    <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" style="display:inline;" data-confirm="Delete this approved quotation? This will also delete the generated invoice and ledger entry.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" style="display:inline;" data-confirm="Delete this quotation?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('quotations.duplicate', $quotation) }}" style="display:inline;" data-confirm="Create a new quotation copied from this one?">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Copy</button>
                </form>
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
                    <div aria-hidden="true"></div>
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
                        <!-- <th>Despatch To</th> -->
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

                            <td>{{ $item->product->name }}<br><small>HSN: {{ $item->product->hsn_code }} · {{ $item->product->description }}</small></td>
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
                @if($quotation->discount_amount > 0)
                    <div class="row"><span>Discount</span><span>-&#8377;{{ number_format($quotation->discount_amount, 2) }}</span></div>
                    <div class="row"><span>Total Amount</span><span>&#8377;{{ number_format($quotation->sub_total - $quotation->discount_amount, 2) }}</span></div>
                @endif
                @if($quotation->gst_applicable)
                    @if($quotation->cgst_amount > 0)<div class="row"><span>CGST (9%)</span><span>&#8377;{{ number_format($quotation->cgst_amount,2) }}</span></div><div class="row"><span>SGST (9%)</span><span>&#8377;{{ number_format($quotation->sgst_amount,2) }}</span></div>@else<div class="row"><span>IGST (18%)</span><span>&#8377;{{ number_format($quotation->igst_amount,2) }}</span></div>@endif
                @endif
                @if($quotation->round_off != 0)
                    <div class="row"><span>Round Off</span><span>{{ $quotation->round_off > 0 ? '+' : '' }}&#8377;{{ number_format($quotation->round_off, 2) }}</span></div>
                @endif
                <div class="row grand"><span>Net Amount</span><span>&#8377;{{ number_format($quotation->total_amount, 2) }}</span></div>
            </div>
        </div>
    </div>
@endsection
