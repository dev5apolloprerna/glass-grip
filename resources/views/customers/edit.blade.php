@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>Edit Customer</h3>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">&larr; Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('customers.update', $customer) }}">
                @csrf
                @method('PUT')
                @include('customers._form')
                <button type="submit" class="btn btn-primary">Update Customer</button>
            </form>
        </div>
    </div>
@endsection
