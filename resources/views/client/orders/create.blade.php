@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create New Order</h1>

    <form action="{{ route('client.orders.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Submit Order</button>
    </form>
</div>
@endsection