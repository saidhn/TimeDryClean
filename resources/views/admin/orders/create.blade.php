@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.create_order') }}</h3>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.orders.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user_id">{{ __('messages.user') }}</label>
                            <select name="user_id" id="user_id"
                                class="form-control @error('user_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_user') }}</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="discount_id">{{ __('messages.discount') }}</label>
                            <select name="discount_id" id="discount_id"
                                class="form-control @error('discount_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_discount') }}</option>
                                @foreach($discounts as $discount)
                                <option value="{{ $discount->id }}">{{ $discount->code }}</option>
                                @endforeach
                            </select>
                            @error('discount_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="client_subscription_id">{{ __('messages.subscription') }}</label>
                            <select name="client_subscription_id" id="client_subscription_id"
                                class="form-control @error('client_subscription_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_subscription') }}</option>
                            </select>
                            @error('client_subscription_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sum_price">{{ __('messages.total_price') }}</label>
                            <input type="number" step="0.01"
                                class="form-control @error('sum_price') is-invalid @enderror" id="sum_price"
                                name="sum_price" value="{{ old('sum_price') }}">
                            @error('sum_price')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="discount_amount">{{ __('messages.discount_amount') }}</label>
                            <input type="number" step="0.01"
                                class="form-control @error('discount_amount') is-invalid @enderror" id="discount_amount"
                                name="discount_amount" value="{{ old('discount_amount') }}">
                            @error('discount_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">{{ __('messages.status') }}</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="Pending">{{ __('messages.pending') }}</option>
                        <option value="Processing">{{ __('messages.processing') }}</option>
                        <option value="Completed">{{ __('messages.completed') }}</option>
                    </select>
                    @error('status')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="order_product_services">{{ __('messages.order_products') }}</label>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('messages.product') }}</th>
                                <th>{{ __('messages.product_service') }}</th>
                                <th>{{ __('messages.quantity') }}</th>
                                <th>{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="order-product-services">
                            <tr>
                                <td>
                                    <select name="order_product_services[0][product_id]"
                                        class="form-control product-select">
                                        <option value="">{{ __('messages.select_product') }}</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="order_product_services[0][product_service_id]"
                                        class="form-control product-service-select">
                                        <option value="">{{ __('messages.select_product_service') }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1" class="form-control"
                                        name="order_product_services[0][quantity]" value="1">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i
                                            class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <button type="button" class="btn btn-sm btn-success add-row">
                                        <i class="fa fa-plus"></i> {{ __('messages.add_row') }}
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Handle product selection and product service loading
        $('.product-select').on('change', function () {
            var productId = $(this).val();
            var $productServiceSelect = $(this).closest('tr').find('.product-service-select');

            $productServiceSelect.empty();
            $productServiceSelect.append('<option value="">{{ __('messages.select_product_service') }}</option>');

            if (productId) {
                $.ajax({
                    url: '/api/products/' + productId + '/product-services', // Assuming you have an API endpoint for this
                    type: 'GET',
                    success: function (data) {
                        $.each(data, function (index, productService) {
                            $productServiceSelect.append('<option value="' + productService.id + '">' + productService.name + '</option>');
                        });
                    }
                });
            }
        });

        // Add new row for order product services
        $('.add-row').on('click', function () {
            var lastRowIndex = $('#order-product-services tr').length - 1;
            var newRow = `
        <tr>
            <td>
                <select name="order_product_services[${lastRowIndex + 1}][product_id]" class="form-control product-select">
                    <option value="">{{ __('messages.select_product') }}</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="order_product_services[${lastRowIndex + 1}][product_service_id]" class="form-control product-service-select">
                    <option value="">{{ __('messages.select_product_service') }}</option>
                </select>
            </td>
            <td>
                <input type="number" min="1" class="form-control" name="order_product_services[${lastRowIndex + 1}][quantity]" value="1">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`;

            $('#order-product-services').append(newRow);

            // Handle product selection and product service loading for the new row
            $('.product-select:last').on('change', function () {
                var productId = $(this).val();
                var $productServiceSelect = $(this).closest('tr').find('.product-service-select');

                $productServiceSelect.empty();
                $productServiceSelect.append('<option value="">{{ __('messages.select_product_service') }}</option>');

                if (productId) {
                    $.ajax({
                        url: '/api/products/' + productId + '/product-services', // Assuming you have an API endpoint for this
                        type: 'GET',
                        success: function (data) {
                            $.each(data, function (index, productService) {
                                $productServiceSelect.append('<option value="' + productService.id + '">' + productService.name + '</option>');
                            });
                        }
                    });
                }
            });

            // Handle remove row button click
            $('.remove-row:last').on('click', function () {
                $(this).closest('tr').remove();
            });
        });
    });
</script>
@endsection