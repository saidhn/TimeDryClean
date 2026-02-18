@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.edit_order') }}</h1>

    @if ($errors->any()) {{-- Check if ANY errors exist --}}
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error) {{-- Iterate through all errors --}}
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form id="edit-order-form" action="{{ route('orders.update', $order) }}" method="POST">
        @csrf
        @method('PUT') {{-- Important for updates --}}

        <div class="card">
            <div class="card-body">
                <div>{{__('messages.id')}}#: {{$order->id}}</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user-select">{{ __('messages.user') }}</label>
                            <select id="user-select" name="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_user') }}</option>
                                @foreach($clients as $user)
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="order_status">{{ __('messages.order_status') }}</label>
                            <select class="form-control" id="order_status" name="order_status">
                                <option value="{{ $order->status }}" selected>{{ $order->statusTranslated() }}</option>
                                <option value="{{ \App\Enums\OrderStatus::PENDING }}" {{ $order->status === \App\Enums\OrderStatus::PENDING ? 'disabled' : '' }}>
                                    {{ __('messages.pending') }}
                                </option>
                                <option value="{{ \App\Enums\OrderStatus::PROCESSING }}" {{ $order->status === \App\Enums\OrderStatus::PROCESSING ? 'disabled' : '' }}>
                                    {{ __('messages.processing') }}
                                </option>
                                <option value="{{ \App\Enums\OrderStatus::SHIPPED }}" {{ $order->status === \App\Enums\OrderStatus::SHIPPED ? 'disabled' : '' }}>
                                    {{ __('messages.shipped') }}
                                </option>
                                <option value="{{ \App\Enums\OrderStatus::COMPLETED }}" {{ $order->status === \App\Enums\OrderStatus::COMPLETED ? 'disabled' : '' }}>
                                    {{ __('messages.completed') }}
                                </option>
                                <option value="{{ \App\Enums\OrderStatus::CANCELLED }}" {{ $order->status === \App\Enums\OrderStatus::CANCELLED ? 'disabled' : '' }}>
                                    {{ __('messages.cancelled') }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-group">
                            <label for="bring_order">{{ __('messages.delivery') }}</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="bring_order" id="bring_order" {{ isset($order->orderDelivery) && ($order->orderDelivery->direction == App\Enums\DeliveryDirection::BOTH || $order->orderDelivery->direction == App\Enums\DeliveryDirection::ORDER_TO_WORK) ? 'checked' : '' }}>
                                <label class="form-check-label" for="bring_order">
                                    {{ __('messages.bring_order') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="return_order" id="return_order" {{ isset($order->orderDelivery) && ($order->orderDelivery->direction == App\Enums\DeliveryDirection::BOTH || $order->orderDelivery->direction == App\Enums\DeliveryDirection::WORK_TO_ORDER) ? 'checked' : '' }}>
                                <label class="form-check-label" for="return_order">
                                    {{ __('messages.return_order') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-check-label" for="delivery_price">{{ __('messages.delivery_price') }}</label>
                            <input type="number" min="0" class="form-control" id="delivery_price" name="delivery_price" value="{{ $order->orderDelivery->price ?? 0 }}">
                            <div id="price-warning" class="text-danger small mt-1" style="display: none;">
                                <i class="fa fa-exclamation-triangle"></i> {{ __('messages.delivery_price_zero_warning') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="driver-select">{{ __('messages.driver') }}</label>
                            <select id="driver-select" name="driver_id" class="form-control @error('driver_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_driver') }}</option>
                                @foreach($drivers as $user) {{-- Assuming users are your drivers --}}
                                <option value="{{ $user->id }}" {{ isset($order->orderDelivery) && optional($order->orderDelivery->driver)->id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('driver_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                            <div id="driver-error" class="text-danger small mt-1" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-center">
                        <button type="button" id="clear-driver-btn" class="btn btn-outline-secondary btn-sm mt-3">
                            <i class="fa fa-times"></i> {{ __('messages.clear') }}
                        </button>
                    </div>
                    <div class="mt-3 mb-3 col-md-2">
                        <label for="province_id" class="form-label">{{ __('messages.province') }}</label>
                        <select id="province_id" class="form-control @error('province_id') is-invalid @enderror"
                            name="province_id">
                            <option value="">{{__('messages.select_province')}}</option>
                            @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" {{ optional(optional($order->orderDelivery)->address)->province_id==$province->id ? 'selected' : ''
                                }}>{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="mt-3 mb-3 col-md-2">
                        <label for="city_id" class="form-label">{{ __('messages.city') }}</label>
                        <select id="city_id" class="form-control @error('city_id') is-invalid @enderror" name="city_id">
                            <option value="">{{__('messages.city')}}</option>
                        </select>
                        @error('city_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="street">{{__('messages.street')}}</label>
                        <input class="form-control" type="text" name="street" id="street" value="{{ optional($order->orderDelivery)->street }}">
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="building">{{__('messages.building')}}</label>
                        <input class="form-control" type="text" name="building" id="building"
                            value="{{ optional($order->orderDelivery)->building }}">
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="floor">{{__('messages.floor')}}</label>
                        <input class="form-control" type="number" name="floor" id="floor" value="{{ optional($order->orderDelivery)->floor }}">
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="apartment_number">{{__('messages.appartment_number')}}</label>
                        <input class="form-control" type="text" name="apartment_number" id="apartment_number"
                            value="{{ optional($order->orderDelivery)->apartment_number }}">
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
                                    <select name="order_product_services[{{ $key }}][product_id]" class="form-control product-select"
                                        data-selected-product="{{ $orderProductService->product_id }}"
                                        data-selected-service="{{ $orderProductService->product_service_id }}"
                                        data-selected-qty="{{ $orderProductService->quantity }}"
                                        data-selected-price="{{ $orderProductService->price_at_order ?? 0 }}">
                                        <option value="">{{ __('messages.select_product') }}</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ $orderProductService->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="order_product_services[{{ $key }}][product_service_id]" class="form-control product-service-select">
                                        <option value="">{{ __('messages.select_product_service') }}</option>
                                    </select>
                                    <small class="text-warning no-services-warning d-none">
                                        <i class="fas fa-exclamation-triangle"></i> {{ __('messages.no_services_configured') }}
                                    </small>
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

                {{-- Always show discount form on edit page --}}
                @include('components.discount-form', ['order' => $order])

                <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).ready(function() {
            let totalPrice = 0;

            const bringOrderCheckbox = document.getElementById('bring_order');
            const returnOrderCheckbox = document.getElementById('return_order');
            const driverSelect = document.getElementById('driver-select');
            const deliveryPriceInput = document.getElementById('delivery_price');
            const form = document.getElementById('edit-order-form');

            function calculateRowPrice(row) {
                const quantity = parseInt(row.find('.quantity-input').val()) || 1;
                const price = parseFloat(row.find('.product-service-select option:selected').data('price')) || 0;
                const rowPrice = quantity * price;
                row.find('.price-display').text(rowPrice.toFixed(2));
                return rowPrice;
            }

            function updateTotal() {
                totalPrice = 0;
                $('#order-product-services tr').each(function() {
                    totalPrice += calculateRowPrice($(this));
                });

                if (bringOrderCheckbox.checked || returnOrderCheckbox.checked) {
                    const deliveryPrice = parseFloat(deliveryPriceInput.value) || 0;
                    totalPrice += deliveryPrice;
                }

                $('#total-price-display').text(totalPrice.toFixed(2));

                const currentSubtotalSpan = document.getElementById('currentSubtotal');
                if (currentSubtotalSpan) {
                    currentSubtotalSpan.textContent = totalPrice.toFixed(2);
                    currentSubtotalSpan.setAttribute('data-value', totalPrice.toFixed(2));
                }
            }

            // Load services via AJAX (same as create page)
            function loadProductServices(productId, serviceSelect, callback) {
                const row = serviceSelect.closest('tr');
                const warning = row.find('.no-services-warning');

                serviceSelect.html('<option value="">{{ __("messages.select_product_service") }}</option>');
                warning.addClass('d-none');

                if (!productId) return;

                fetch(`/api/products/${productId}/services`, { credentials: 'include' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.data.services.length === 0) {
                            warning.removeClass('d-none');
                        } else {
                            data.data.services.forEach(service => {
                                const option = $('<option></option>')
                                    .val(service.id)
                                    .text(`${service.name} - ${service.price} {{ __('messages.currency_symbol') }}`)
                                    .attr('data-price', service.price);
                                serviceSelect.append(option);
                            });
                        }
                        if (callback) callback();
                        updateTotal();
                    })
                    .catch(error => {
                        console.error('Error loading services:', error);
                        warning.removeClass('d-none');
                    });
            }

            // Load services for all existing rows on page load
            $('#order-product-services tr').each(function() {
                const row = $(this);
                const productSelect = row.find('.product-select');
                const serviceSelect = row.find('.product-service-select');
                const productId = productSelect.data('selected-product');
                const serviceId = productSelect.data('selected-service');

                if (productId) {
                    loadProductServices(productId, serviceSelect, function() {
                        if (serviceId) {
                            serviceSelect.val(serviceId);
                        }
                        updateTotal();
                    });
                }
            });

            // Handle product change
            $('#order-product-services').on('change', '.product-select', function() {
                const serviceSelect = $(this).closest('tr').find('.product-service-select');
                loadProductServices($(this).val(), serviceSelect);
            });

            $('#order-product-services').on('input', '.quantity-input', function() {
                updateTotal();
            });

            $('#order-product-services').on('change', '.product-service-select', function() {
                updateTotal();
            });

            $('.add-row').on('click', function() {
                var lastRowIndex = $('#order-product-services tr').length;
                var newRow = `
            <tr>
                <td>
                    <select name="order_product_services[${lastRowIndex}][product_id]" class="form-control product-select">
                        <option value="">{{ __('messages.select_product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="order_product_services[${lastRowIndex}][product_service_id]" class="form-control product-service-select">
                        <option value="">{{ __('messages.select_product_service') }}</option>
                    </select>
                    <small class="text-warning no-services-warning d-none">
                        <i class="fas fa-exclamation-triangle"></i> {{ __('messages.no_services_configured') }}
                    </small>
                </td>
                <td>
                    <input type="number" min="1" class="form-control quantity-input" name="order_product_services[${lastRowIndex}][quantity]" value="1">
                </td>
                <td class="unit_price">
                    <span class="price-display">0</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;
                $('#order-product-services').append(newRow);
                updateTotal();
            });

            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                updateTotal();
            });


            const driverError = document.getElementById('driver-error');
            const clearDriverBtn = document.getElementById('clear-driver-btn');
            const priceWarning = document.getElementById('price-warning');
            const driverRequiredMsg = '{{ __("messages.driver_required") }}';
            const deliveryOptionRequiredMsg = '{{ __("messages.delivery_option_required") }}';

            function validateDriverAndDelivery() {
                const driverSelected = driverSelect.value !== "";
                const checkboxSelected = bringOrderCheckbox.checked || returnOrderCheckbox.checked;
                const deliveryPrice = parseFloat(deliveryPriceInput.value) || 0;
                
                driverError.style.display = 'none';
                driverError.textContent = '';
                driverSelect.classList.remove('is-invalid');
                bringOrderCheckbox.classList.remove('is-invalid');
                returnOrderCheckbox.classList.remove('is-invalid');
                
                // Show price warning if driver selected but price is 0
                if (driverSelected && deliveryPrice === 0) {
                    priceWarning.style.display = 'block';
                } else {
                    priceWarning.style.display = 'none';
                }
                
                // If driver is selected but no checkbox is selected
                if (driverSelected && !checkboxSelected) {
                    driverError.textContent = deliveryOptionRequiredMsg;
                    driverError.style.display = 'block';
                    return false;
                }
                
                // If checkbox is selected but no driver is selected
                if (checkboxSelected && !driverSelected) {
                    driverError.textContent = driverRequiredMsg;
                    driverError.style.display = 'block';
                    driverSelect.classList.add('is-invalid');
                    return false;
                }
                
                return true;
            }

            function toggleDriverRequired() {
                const deliveryRequired = bringOrderCheckbox.checked || returnOrderCheckbox.checked;
                driverSelect.required = deliveryRequired;
                deliveryPriceInput.required = deliveryRequired;
                document.getElementById('province_id').required = deliveryRequired;
                document.getElementById('city_id').required = deliveryRequired;
                driverSelect.setCustomValidity("");
                validateDriverAndDelivery();
            }

            // Clear driver button functionality
            clearDriverBtn.addEventListener('click', function() {
                driverSelect.value = '';
                // If using TomSelect, also clear it
                if (driverSelect.tomselect) {
                    driverSelect.tomselect.clear();
                }
                bringOrderCheckbox.checked = false;
                returnOrderCheckbox.checked = false;
                validateDriverAndDelivery();
                updateTotal();
            });

            toggleDriverRequired();

            bringOrderCheckbox.addEventListener('change', toggleDriverRequired);
            returnOrderCheckbox.addEventListener('change', toggleDriverRequired);
            driverSelect.addEventListener('change', validateDriverAndDelivery);

            bringOrderCheckbox.addEventListener('change', updateTotal);
            returnOrderCheckbox.addEventListener('change', updateTotal);
            deliveryPriceInput.addEventListener('input', updateTotal);

            // Form submit validation
            form.addEventListener('submit', function(event) {
                if (!validateDriverAndDelivery()) {
                    event.preventDefault();
                    driverSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            $('#order-product-services tr').each(function() {
                calculateRowPrice($(this));
            });
            updateTotal();
        });
        //address province and city select
        const citiesByProvince = @json(\App\Models\City:: select('id', 'name', 'province_id') -> get() -> groupBy('province_id'));

        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');

        provinceSelect.addEventListener('change', function () {
            updateAddress(this.value);
        });
        function updateAddress(val, selectedCityId = null) {
            citySelect.innerHTML = '<option value="">{{__('messages.select_city')}}</option>'; // Clear existing options

            const provinceId = val;

            if (provinceId && citiesByProvince[provinceId]) {
                citiesByProvince[provinceId].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.text = city.name;
                    citySelect.appendChild(option);
                });
                
                // Set selected city if provided
                if (selectedCityId) {
                    citySelect.value = selectedCityId;
                }
            }
        }
        
        // Initialize city select on page load if province is selected
        @if(optional(optional($order->orderDelivery)->address)->province_id)
            updateAddress(provinceSelect.value, {{ optional(optional($order->orderDelivery)->address)->city_id ?? 'null' }});
        @endif
    });
</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // User Select2
        new TomSelect('#user-select', {
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            load: function(query, callback) {
                fetch(`/users/search?q=${encodeURIComponent(query)}&user_type={{App\Enums\UserType::CLIENT}}`)
                    .then(response => response.json())
                    .then(json => {
                        if (json.data && json.data.length) {
                            callback(json.data);
                        } else {
                            callback([]);
                        }
                    })
                    .catch(() => {
                        callback([]);
                    });
            },
            render: {
                option: function(item, escape) {
                    return `
                    <div>
                        <strong>${escape(item.name)}</strong>
                        <div class="text-muted">ID: ${escape(item.id)}, Mobile: ${escape(item.mobile)}</div>
                    </div>
                    `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });

        // Driver Select2
        new TomSelect('#driver-select', {
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            load: function(query, callback) {
                fetch(`/users/search?q=${encodeURIComponent(query)}&user_type={{App\Enums\UserType::DRIVER}}`)
                    .then(response => response.json())
                    .then(json => {
                        if (json.data && json.data.length) {
                            callback(json.data);
                        } else {
                            callback([]);
                        }
                    })
                    .catch(() => {
                        callback([]);
                    });
            },
            render: {
                option: function(item, escape) {
                    return `
                    <div>
                        <strong>${escape(item.name)}</strong>
                        <div class="text-muted">ID: ${escape(item.id)}</div>
                    </div>
                    `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });
    });
</script>
@endpush
@endsection