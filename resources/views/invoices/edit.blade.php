@extends('layouts.app')

@section('title', 'Edit ' . $invoice->typeLabel() . ' ' . $invoice->invoice_number)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>Edit {{ $invoice->typeLabel() }} &mdash; {{ $invoice->invoice_number }}</h3>
            <a href="{{ route('quotations.show', $invoice->quotation) }}" class="btn btn-secondary btn-sm">&larr; Back</a>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Quotation <strong>{{ $invoice->quotation->quotation_number }}</strong> &middot;
                {{ $invoice->customer->name }} &middot;
                Share {{ rtrim(rtrim(number_format($invoice->split_percentage, 2), '0'), '.') }}% &middot;
                Value &#8377;{{ number_format($invoice->total_amount, 2) }}
            </p>

            <form method="POST" action="{{ route('invoices.update', $invoice) }}">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label for="invoice_number">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" id="invoice_number" name="invoice_number" class="form-control" value="{{ old('invoice_number', $invoice->invoice_number) }}" maxlength="255" required autocomplete="off">
                        <div class="form-hint">Number, date and reference are common &mdash; they are updated on both the Invoice and the A Invoice.</div>
                        @error('invoice_number')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" id="invoice_date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date->toDateString()) }}" required>
                        @error('invoice_date')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="other_reference">Reference Number</label>
                        <input type="text" id="other_reference" name="other_reference" class="form-control" value="{{ old('other_reference', $invoice->other_reference) }}" maxlength="255" autocomplete="off">
                        @error('other_reference')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>

                <a href="{{ route('quotations.show', $invoice->quotation) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
