@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
    <div class="card">
        <div class="card-header"><h3>Sales Report</h3></div>
        <div class="card-body">
            <form method="GET" class="filters-bar">
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="form-group">
                    <label>Customer</label>
                    <select name="customer_id" class="form-control">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) $customerId === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="{{ route('reports.sales') }}" class="btn btn-secondary">Clear</a>
            </form>

            <div class="stat-grid" style="margin-top:20px;">
                <div class="stat-card">
                    <div class="label">Total Invoices</div>
                    <div class="value">{{ $totals['count'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Sub Total</div>
                    <div class="value">&#8377;{{ number_format($totals['sub_total'], 2) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">GST Collected</div>
                    <div class="value">&#8377;{{ number_format($totals['gst_amount'], 2) }}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Grand Total</div>
                    <div class="value">&#8377;{{ number_format($totals['total_amount'], 2) }}</div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Created By</th>
                            <th class="text-right">Sub Total</th>
                            <th class="text-right">GST</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr onclick="window.location='{{ route('invoices.show', $invoice) }}'" style="cursor:pointer;">
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td>{{ $invoice->customer->name }}</td>
                                <td>{{ $invoice->quotation->user->name ?? '-' }}</td>
                                <td class="text-right">&#8377;{{ number_format($invoice->sub_total, 2) }}</td>
                                <td class="text-right">&#8377;{{ number_format($invoice->gst_amount, 2) }}</td>
                                <td class="text-right">&#8377;{{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No invoices found for this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
