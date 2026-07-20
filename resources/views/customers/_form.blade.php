@php $c = $customer ?? null; @endphp

<div class="form-row">
    <div class="form-group">
        <label for="name">Customer Name *</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $c->name ?? '') }}" required>
    </div>
    <div class="form-group">
        <label for="contact_person">Contact Person</label>
        <input type="text" class="form-control" id="contact_person" name="contact_person" value="{{ old('contact_person', $c->contact_person ?? '') }}">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="phone">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $c->phone ?? '') }}">
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $c->email ?? '') }}">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="gst_number">GST Number</label>
        <input type="text" class="form-control" id="gst_number" name="gst_number" value="{{ old('gst_number', $c->gst_number ?? '') }}">
    </div>
    <div class="form-group">
        <label for="opening_balance">Opening Balance (&#8377;)</label>
        <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', $c->opening_balance ?? 0) }}" required>
        <div class="form-hint">Positive = customer owes us (due). Negative = advance already with us.</div>
    </div>
</div>

<div class="form-group">
    <label for="address">Address</label>
    <textarea class="form-control" id="address" name="address">{{ old('address', $c->address ?? '') }}</textarea>
</div>
