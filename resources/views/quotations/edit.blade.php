@extends('layouts.app')

@section('title', 'Edit Quotation')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>Edit Quotation {{ $quotation->quotation_number }}</h3>
            <a href="{{ route('quotations.show', $quotation) }}" class="btn btn-secondary btn-sm">&larr; Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('quotations.update', $quotation) }}">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_id">Customer *</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">Select customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $quotation->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quotation_date">Quotation Date *</label>
                        <input type="date" class="form-control" id="quotation_date" name="quotation_date" value="{{ old('quotation_date', $quotation->quotation_date->toDateString()) }}" required>
                    </div>
                    <div class="form-group">
                        <label>GST</label>
                        <div class="checkbox-row" style="margin-top:10px;">
                            <input type="checkbox" id="gst_applicable" name="gst_applicable" value="1" {{ old('gst_applicable', $quotation->gst_applicable) ? 'checked' : '' }}>
                            <label for="gst_applicable" style="margin:0; font-weight:400;">Apply GST @ 18%</label>
                        </div>
                    </div>
                </div>

                <div class="card" style="box-shadow:none; border:1px solid var(--color-border); margin-top:10px;">
                    <div class="card-header">
                        <h3>Products</h3>
                        <button type="button" id="addItemBtn" class="btn btn-secondary btn-sm">+ Add Product Line</button>
                    </div>
                    <div class="card-body">
                        <div id="itemsContainer" data-next-index="{{ $quotation->items->count() }}">
                            @foreach($quotation->items as $i => $item)
                                @include('quotations._item_row', ['index' => $i, 'products' => $products, 'item' => $item])
                            @endforeach
                        </div>

                        <div class="totals-box">
                            <div class="row"><span>Sub Total</span><span>&#8377;<span id="summarySubTotal">0.00</span></span></div>
                            <div class="row" id="summaryGstRow" style="display:none;"><span>GST (18%)</span><span>&#8377;<span id="summaryGstAmount">0.00</span></span></div>
                            <div class="row grand"><span>Total</span><span>&#8377;<span id="summaryTotal">0.00</span></span></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Update Quotation</button>
                </div>
            </form>
        </div>
    </div>

    <template id="itemRowTemplate">
        @include('quotations._item_row', ['index' => '__INDEX__', 'products' => $products, 'item' => null])
    </template>
@endsection
