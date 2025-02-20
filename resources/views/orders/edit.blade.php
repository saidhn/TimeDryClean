@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.edit_order') }}</h1>

    <form action="{{ route('orders.update', $order) }}" method="POST">
        @csrf
        @method('PUT') {{-- Important for updates --}}

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user-select">{{ __('messages.user') }}</label>
                            <select id="user-select" name="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_user') }}</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $order->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6"></div>

                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-group">
                            <label for="bring_order">{{ __('messages.delivery') }}</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="bring_order" id="bring_order" {{ $order->bring_order ? 'checked' : '' }}>
                                <label class="form-check-label" for="bring_order">
                                    {{ __('messages.bring_order') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="return_order" id="return_order" {{ $order->return_order ? 'checked' : '' }}>
                                <label class="form-check-label" for="return_order">
                                    {{ __('messages.return_order') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-check-label" for="delivery_price">{{ __('messages.delivery_price') }}</label>
                            <input type="number" min="0" class="form-control" id="delivery_price" name="delivery_price" value="{{ $order->delivery_price ?? 0 }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="driver-select">{{ __('messages.driver') }}</label>
                            <select id="driver-select" name="driver_id" class="form-control @error('driver_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_driver') }}</option>
                                @foreach($users as $user) {{-- Assuming users are your drivers --}}
                                <option value="{{ $user->id }}" {{ $order->driver_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('driver_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('messages.order_products') }}</label>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('messages.product') }}</th>
                                <th>{{ __('messages.product_service') }}</th>
                                <th>{{ __('messages.quantity') }}</th>
                                <th>{{ __('messages.price') }}</th>
                                <th>{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="order-product-services">
                            @foreach($order->orderProductServices as $key => $orderProductService)
                            <tr>
                                <td>
                                    <select name="order_product_services[{{ $key }}][product_id]" class="form-control product-select">
                                        <option value="">{{ __('messages.select_product') }}</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ $orderProductService->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="order_product_services[{{ $key }}][product_service_id]" class="form-control product-service-select">
                                        <option value="">{{ __('messages.select_product_service') }}</option>
                                        @foreach($product_services as $product_service)
                                        <option value="{{ $product_service->id }}" data-price="{{ $product_service->price }}" {{ $orderProductService->product_service_id == $product_service->id ? 'selected' : '' }}>
                                            {{ $product_service->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1" class="form-control quantity-input" name="order_product_services[{{ $key }}][quantity]" value="{{ $orderProductService->quantity }}">
                                </td>
                                <td class="unit_price">
                                    <span class="price-display">0</span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success add-row">
                                        <i class="fa fa-plus"></i> {{ __('messages.add') }}
                                    </button>
                                </td>
                                <td colspan="2" class="text-end"><strong>{{ __('messages.total_price') }}:</strong></td>
                                <td id="total-price-display">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
            </div>
        </div>
    </form>
</div>

<script>
    // ... (JavaScript code from create.blade.php - mostly the same, but adapt where needed)
    // Make sure to recalculate prices on load and when editing existing rows.
    $(document).ready(function() {
        // ... (rest of your existing JS)

        $('#order-product-services tr').each(function() {
            calculateRowPrice($(this));
        });
        updateTotal();
    });
</script>
@endsection