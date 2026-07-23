@extends('layouts.app')

@section('title', 'New Quotation')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>New Quotation</h3>
            <a href="{{ route('quotations.index') }}" class="btn btn-secondary btn-sm">&larr; Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('quotations.store') }}">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_id">Customer *</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">Select customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quotation_date">Quotation Date *</label>
                        <input type="date" class="form-control" id="quotation_date" name="quotation_date" value="{{ old('quotation_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="form-group">
                        <label>GST</label>
                        <div class="checkbox-row" style="margin-top:10px;">
                            <input type="checkbox" id="gst_applicable" name="gst_applicable" value="1" {{ old('gst_applicable') ? 'checked' : '' }}>
                            <label for="gst_applicable" style="margin:0; font-weight:400;">Apply GST @ 18%</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="discount_amount">Discount Amount (&#8377;) <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="discount_amount" name="discount_amount" value="{{ old('discount_amount', 0) }}">
                    </div>
                </div>

                <div class="card" style="box-shadow:none; border:1px solid var(--color-border); margin-top:10px;">
                    <div class="card-header">
                        <h3>Products</h3>
                        <button type="button" id="addItemBtn" class="btn btn-secondary btn-sm">+ Add Product Line</button>
                    </div>
                    <div class="card-body">
                        <div id="itemsContainer" data-next-index="0"></div>

                        <div class="totals-box">
                            <div class="row"><span>Sub Total</span><span>&#8377;<span id="summarySubTotal">0.00</span></span></div>
                            <div class="row" id="summaryDiscountRow" style="display:none;"><span>Discount</span><span>-&#8377;<span id="summaryDiscountAmount">0.00</span></span></div>
                            <div class="row" id="summaryGstRow" style="display:none;"><span>GST (18%)</span><span>&#8377;<span id="summaryGstAmount">0.00</span></span></div>
                            
                            <div class="row"><span>Round Off</span><span id="summaryRoundOff">&#8377;0.00</span></div>
                            <div class="row grand"><span>Total</span><span>&#8377;<span id="summaryTotal">0.00</span></span></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Save as Draft</button>
                    <span class="form-hint">You can edit this quotation and add/remove products until it's approved.</span>
                </div>
            </form>
        </div>
    </div>

    <template id="itemRowTemplate">
        @include('quotations._item_row', ['index' => '__INDEX__', 'products' => $products, 'item' => null])
    </template>
@endsection
