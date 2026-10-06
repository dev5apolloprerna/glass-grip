@extends('layouts.app')

@section('title', 'Generate Invoice - ' . $quotation->quotation_number)

@php
    // Everything comes from the SAVED quotation - nothing is re-calculated.
    $isCgst = (float) $quotation->cgst_amount > 0;
    $splitData = [
        'items' => $quotation->items->map(fn ($i) => (float) $i->amount)->values(),
        'discount' => (float) $quotation->discount_amount,
        'admin' => (float) $quotation->admin_charges,
        'handling' => (float) $quotation->material_handling_charges,
        'cgst' => (float) $quotation->cgst_amount,
        'sgst' => (float) $quotation->sgst_amount,
        'igst' => (float) $quotation->igst_amount,
    ];
    $q = $quotation;
    $qTaxable = $q->sub_total - $q->discount_amount + $q->admin_charges + $q->material_handling_charges;
    $m = fn ($v) => '₹' . number_format((float) $v, 2);
@endphp

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>Generate Invoice &mdash; {{ $quotation->quotation_number }}</h3>
            <a href="{{ route('quotations.show', $quotation) }}" class="btn btn-secondary btn-sm">&larr; Back</a>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Customer: <strong>{{ $quotation->customer->name }}</strong> &middot;
                Quotation Net Amount: <strong>&#8377;{{ number_format($quotation->total_amount, 2) }}</strong>
            </p>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('quotations.generate-invoice', $quotation) }}" id="generateInvoiceForm">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label for="invoice_number">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" id="invoice_number" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}" placeholder="Enter invoice number" maxlength="255" required autocomplete="off">
                        <div class="form-hint">Same number is used on the Invoice and the A Invoice.</div>
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" id="invoice_date" name="invoice_date" class="form-control" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="other_reference">Reference Number</label>
                        <input type="text" id="other_reference" name="other_reference" class="form-control" value="{{ old('other_reference') }}" placeholder="Enter reference number" maxlength="255" autocomplete="off">
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table split-table">
                        <thead>
                            <tr>
                                <th style="width:24%"></th>
                                <th class="text-right">Quotation</th>
                                <th class="text-right">Invoice <small>({{ $q->gst_amount > 0 ? 'with GST' : 'no GST' }})</small></th>
                                <th class="text-right">A Invoice <small>(without GST)</small></th>
                                <th class="text-right">Invoice + A Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Share (%)</strong> <span class="text-danger">*</span></td>
                                <td class="text-right">100%</td>
                                <td><input type="number" step="0.01" min="0" max="100" id="invoice_percentage" name="invoice_percentage" class="form-control text-right" value="{{ old('invoice_percentage', 100) }}" required></td>
                                <td><input type="number" step="0.01" min="0" max="100" id="a_invoice_percentage" name="a_invoice_percentage" class="form-control text-right" value="{{ old('a_invoice_percentage', 0) }}" required></td>
                                <td class="text-right"><strong id="pct_total">100%</strong></td>
                            </tr>
                            <tr><td>Sub Total</td><td class="text-right">{{ $m($q->sub_total) }}</td><td class="text-right" data-v="gst.sub"></td><td class="text-right" data-v="a.sub"></td><td class="text-right" data-v="t.sub"></td></tr>
                            <tr data-row="discount"><td>Discount</td><td class="text-right">{{ $m($q->discount_amount) }}</td><td class="text-right" data-v="gst.discount"></td><td class="text-right" data-v="a.discount"></td><td class="text-right" data-v="t.discount"></td></tr>
                            <tr data-row="charges"><td>Admin + Material Handling Charges</td><td class="text-right">{{ $m($q->admin_charges + $q->material_handling_charges) }}</td><td class="text-right" data-v="gst.charges"></td><td class="text-right" data-v="a.charges"></td><td class="text-right" data-v="t.charges"></td></tr>
                            <tr><td>Taxable Amount</td><td class="text-right">{{ $m($qTaxable) }}</td><td class="text-right" data-v="gst.taxable"></td><td class="text-right" data-v="a.taxable"></td><td class="text-right" data-v="t.taxable"></td></tr>
                            <tr @unless($q->gst_amount > 0) style="display:none" @endunless><td>GST ({{ $isCgst ? 'CGST 9% + SGST 9%' : 'IGST 18%' }})</td><td class="text-right">{{ $m($q->gst_amount) }}</td><td class="text-right" data-v="gst.gst"></td><td class="text-right">&mdash;</td><td class="text-right" data-v="t.gst"></td></tr>
                            <tr><td>Round Off</td><td class="text-right">{{ $m($q->round_off) }}</td><td class="text-right" data-v="gst.round"></td><td class="text-right" data-v="a.round"></td><td class="text-right" data-v="t.round"></td></tr>
                            <tr style="font-weight:700;"><td>Invoice Value</td><td class="text-right">{{ $m($q->total_amount) }}</td><td class="text-right" data-v="gst.total"></td><td class="text-right" data-v="a.total"></td><td class="text-right" data-v="t.total"></td></tr>
                        </tbody>
                    </table>
                </div>

                <p id="pct_error" class="text-danger" style="display:none;">Invoice % + A Invoice % must be exactly 100.</p>
                <p class="form-hint">
                    Values are taken from the saved quotation (GST is not calculated again).
                    Invoice = Invoice % of every item amount, discount, charges and the quotation's GST.
                    A Invoice = remaining % of the item amounts, discount and charges, without GST.
                    If a side is 0%, that invoice is not created.
                </p>

                <div style="margin-top:12px;">
                    <a href="{{ route('quotations.show', $quotation) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="generateBtn">Generate Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const D = @json($splitData);
        const gstPctEl = document.getElementById('invoice_percentage');
        const aPctEl = document.getElementById('a_invoice_percentage');
        const r2 = v => Math.round((v + Number.EPSILON) * 100) / 100;
        const fmt = v => '₹' + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Same logic as App\Services\InvoiceSplitter / Invoice::splitValue()
        function share(value, isGst, pct) {
            if (pct >= 100) return r2(value);
            return isGst ? r2(value * pct / 100) : r2(value - r2(value * (100 - pct) / 100));
        }

        function calc(isGst, pct) {
            let sub = 0;
            D.items.forEach(amount => { sub += share(amount, isGst, pct); });
            sub = r2(sub);
            const discount = share(D.discount, isGst, pct);
            const charges = r2(share(D.admin, isGst, pct) + share(D.handling, isGst, pct));
            const taxable = r2(sub - discount + charges);
            const gst = isGst ? r2(share(D.cgst, true, pct) + share(D.sgst, true, pct) + share(D.igst, true, pct)) : 0;
            const before = taxable + gst;
            const total = Math.round(before);
            return { sub, discount, charges, taxable, gst, round: r2(total - before), total };
        }

        function render() {
            const gp = parseFloat(gstPctEl.value) || 0;
            const ap = parseFloat(aPctEl.value) || 0;
            const g = gp > 0 ? calc(true, gp) : null;
            const a = ap > 0 ? calc(false, ap) : null;
            const zero = { sub: 0, discount: 0, charges: 0, taxable: 0, gst: 0, round: 0, total: 0 };
            const gg = g || zero, aa = a || zero;
            const vals = { gst: gg, a: aa, t: {} };
            Object.keys(zero).forEach(k => vals.t[k] = r2(gg[k] + aa[k]));

            document.querySelectorAll('[data-v]').forEach(el => {
                const [side, key] = el.dataset.v.split('.');
                if ((side === 'gst' && !g) || (side === 'a' && !a)) { el.textContent = '—'; return; }
                el.textContent = fmt(vals[side][key]);
            });
            document.querySelector('[data-row="discount"]').style.display = D.discount > 0 ? '' : 'none';
            document.querySelector('[data-row="charges"]').style.display = (D.admin + D.handling) > 0 ? '' : 'none';

            const sum = r2(gp + ap);
            const ok = Math.abs(sum - 100) < 0.001;
            document.getElementById('pct_total').textContent = sum + '%';
            document.getElementById('pct_total').className = ok ? 'text-success' : 'text-danger';
            document.getElementById('pct_error').style.display = ok ? 'none' : '';

            return ok;
        }

        // Typing one % auto-fills the other so both always total 100.
        gstPctEl.addEventListener('input', () => {
            const v = Math.min(100, Math.max(0, parseFloat(gstPctEl.value) || 0));
            aPctEl.value = r2(100 - v);
            render();
        });
        aPctEl.addEventListener('input', () => {
            const v = Math.min(100, Math.max(0, parseFloat(aPctEl.value) || 0));
            gstPctEl.value = r2(100 - v);
            render();
        });

        document.getElementById('generateInvoiceForm').addEventListener('submit', e => {
            if (!render()) { e.preventDefault(); return; }
            if (!confirm('Generate invoice(s)? The quotation can no longer be edited after this.')) e.preventDefault();
        });

        render();
    })();
    </script>
@endsection
