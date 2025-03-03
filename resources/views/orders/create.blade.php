@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.create_order') }}</h3>

    @if ($errors->any()) {{-- Check if ANY errors exist --}}
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error) {{-- Iterate through all errors --}}
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form id="create-order-form" action="{{ route('orders.store') }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user-select">{{ __('messages.user') }}</label>
                            <select id="user-select" name="user_id"
                                class="form-control @error('user_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_user') }}</option>
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
                                <input class="form-check-input" type="checkbox" name="bring_order" id="bring_order"
                                    value="on" {{ old('bring_order') ? 'checked' : '' }}>

                                <label class="form-check-label" for="bring_order">
                                    {{ __('messages.bring_order') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="return_order" id="return_order" {{
                                    old('return_order') ? 'checked' : '' }}>
                                <label class="form-check-label" for="return_order">
                                    {{ __('messages.return_order') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-check-label" for="delivery_price">{{ __('messages.delivery_price')
                                }}</label>
                            <input type="number" min="0" class="form-control" id="delivery_price" name="delivery_price"
                                value="{{ old('delivery_price', 0) }}">
                        </div>
                    </div>
                    <div class=" col-md-6">
                        <div class="form-group">
                            <label for="driver-select">{{ __('messages.driver') }}</label>
                            <select id="driver-select" name="driver_id"
                                class="form-control @error('driver_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_driver') }}</option>
                               
                            </select>
                            @error('driver_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>


                    <div class="mt-3 mb-3 col-md-2">
                        <label for="province_id" class="form-label">{{ __('messages.province') }}</label>
                        <select id="province_id" class="form-control @error('province_id') is-invalid @enderror"
                            name="province_id" required>
                            <option value="">{{__('messages.select_province')}}</option>
                            @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" {{ old('province_id')==$province->id ? 'selected' : ''
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
                        <select id="city_id" class="form-control @error('city_id') is-invalid @enderror" name="city_id"
                            required disabled>
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
                        <input class="form-control" type="text" name="street" id="street" value="{{ old('street') }}">
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="building">{{__('messages.building')}}</label>
                        <input class="form-control" type="text" name="building" id="building"
                            value="{{ old('building') }}">
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="floor">{{__('messages.floor')}}</label>
                        <input class="form-control" type="number" name="floor" id="floor" value="{{ old('floor') }}">
                    </div>

                    <div class="mt-3 mb-3 col-md-2">
                        <label class="form-label" for="apartment_number">{{__('messages.appartment_number')}}</label>
                        <input class="form-control" type="text" name="apartment_number" id="apartment_number"
                            value="{{ old('apartment_number') }}">
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
                            @for ($i = 0; $i < (old('order_product_services') ? count(old('order_product_services')) :
                                1); $i++) <tr>
                                <td>
                                    <select name="order_product_services[{{ $i }}][product_id]"
                                        class="form-control product-select">
                                        <option value="">{{ __('messages.select_product') }}</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ old('order_product_services.' . $i
                                            . '.product_id' )==$product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="order_product_services[{{ $i }}][product_service_id]"
                                        class="form-control product-service-select">
                                        <option value="">{{ __('messages.select_product_service') }}</option>
                                        @foreach($product_services as $product_service)
                                        <option value="{{ $product_service->id }}"
                                            data-price="{{ $product_service->price }}" {{ old('order_product_services.'
                                            . $i . '.product_service_id' )==$product_service->id ? 'selected' : '' }}>
                                            {{ $product_service->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1" class="form-control quantity-input"
                                        name="order_product_services[{{ $i }}][quantity]"
                                        value="{{ old('order_product_services.' . $i . '.quantity', 1) }}">
                                </td>
                                <td class="unit_price">
                                    <span class="price-display">0</span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i
                                            class="fa fa-trash"></i></button>
                                </td>
                                </tr>
                                @endfor

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

                <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            </div>
        </div>
    </form>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        $(document).ready(function () {
            let totalPrice = 0;

            const bringOrderCheckbox = document.getElementById('bring_order');
            const returnOrderCheckbox = document.getElementById('return_order');
            const driverSelect = document.getElementById('driver-select');
            const deliveryPriceInput = document.getElementById('delivery_price');

            const province_id = document.getElementById('province_id');
            const city_id = document.getElementById('city_id');
            const street = document.getElementById('street');
            const building = document.getElementById('building');
            const floor = document.getElementById('floor');
            const apartment_number = document.getElementById('apartment_number');

            const form = document.getElementById('create-order-form');

            function calculateRowPrice(row) {
                const quantity = parseInt(row.find('.quantity-input').val()) || 1;
                const price = parseFloat(row.find('.product-service-select option:selected').data('price')) || 0;
                const rowPrice = quantity * price;
                row.find('.price-display').text(rowPrice.toFixed(2));
                return rowPrice;
            }

            function updateTotal() {
                totalPrice = 0;
                $('#order-product-services tr').each(function () {
                    totalPrice += calculateRowPrice($(this));
                });

                if (bringOrderCheckbox.checked || returnOrderCheckbox.checked) {
                    const deliveryPrice = parseFloat(deliveryPriceInput.value) || 0;
                    totalPrice += deliveryPrice;
                }

                $('#total-price-display').text(totalPrice.toFixed(2));
            }

            $('#order-product-services tr').each(function () {
                calculateRowPrice($(this));
            });
            updateTotal();

            $('#order-product-services').on('input', '.quantity-input', function () {
                updateTotal();
            });

            $('#order-product-services').on('change', '.product-service-select', function () {
                updateTotal();
            });

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
                        @foreach($product_services as $product_service)
                            <option value="{{ $product_service->id }}" data-price="{{ $product_service->price }}">
                                {{ $product_service->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" min="1" class="form-control quantity-input" name="order_product_services[${lastRowIndex + 1}][quantity]" value="1">
                </td>
                <td class="unit_price">
                    <span class="price-display">0</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;

                $('#order-product-services').append(newRow);

                var orders_arr = $('#order-product-services tr');
                var orders_len = orders_arr.length;
                if (orders_len > 1) {
                    var selected_product = $(orders_arr[orders_len - 2]).find('.product-select').val();
                    var selected_service = $(orders_arr[orders_len - 2]).find('.product-service-select').val();

                    $(orders_arr[orders_len - 1]).find('.product-select').val(selected_product);
                    $(orders_arr[orders_len - 1]).find('.product-service-select').val(selected_service);
                }

                $(document).on('change', '.product-select', function () { });

                $(document).on('click', '.remove-row', function () {
                    $(this).closest('tr').remove();
                    updateTotal();
                });

                updateTotal();
            });

            $(document).on('change', '.product-select', function () { });

            $(document).on('click', '.remove-row', function () {
                $(this).closest('tr').remove();
                updateTotal();
            });


            function toggleDriverRequired() {

                driverSelect.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                deliveryPriceInput.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                province_id.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                city_id.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                street.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                building.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                floor.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                apartment_number.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);

                driverSelect.setCustomValidity("");
            }

            toggleDriverRequired();

            bringOrderCheckbox.addEventListener('change', toggleDriverRequired);
            returnOrderCheckbox.addEventListener('change', toggleDriverRequired);

            bringOrderCheckbox.addEventListener('change', updateTotal);
            returnOrderCheckbox.addEventListener('change', updateTotal);
            deliveryPriceInput.addEventListener('input', updateTotal);

            form.addEventListener('submit', function (event) {
                toggleDriverRequired();
                if (driverSelect.required && driverSelect.value === "") {
                    driverSelect.setCustomValidity("Please select a driver.");
                    driverSelect.reportValidity();
                    event.preventDefault();
                } else {
                    driverSelect.setCustomValidity("");
                }
            });
        });
        //address province and city select
        const citiesByProvince = @json(\App\Models\City:: select('id', 'name', 'province_id') -> get() -> groupBy('province_id'));

        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');

        provinceSelect.addEventListener('change', function () {
            updateAddress(this.value);
        });
        function updateAddress(val) {
            citySelect.innerHTML = '<option value="">{{__('messages.select_city')}}</option>'; // Clear existing options
            citySelect.disabled = true;

            const provinceId = val;

            if (provinceId && citiesByProvince[provinceId]) {
                citiesByProvince[provinceId].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.text = city.name;
                    citySelect.appendChild(option);
                });
                citySelect.disabled = false;
            }
        }
        if ({{ old('province_id')!=null ? 'true' : 'false'}}){
            updateAddress(provinceSelect.value);
            citySelect.value = {{ old('city_id')??'-1' }};
        }
    });
</script>


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // User Select2
        let clientSelect = new TomSelect('#user-select', {
            valueField: 'id',
            labelField: 'name',
            searchField: ['id', 'name', 'mobile'], // Allow search by ID, name or mobile
            load: function (query, callback) {
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
                option: function (item, escape) {
                    return `
                    <div>
                        <strong>${escape(item.name)}</strong>
                        <div class="text-muted">ID: ${escape(item.id)}, Mobile: ${escape(item.mobile)}</div>
                    </div>
                    `;
                },
                item: function (item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });
            // Load initial data (all clients)
            fetch(`/users/search?q=&user_type={{App\Enums\UserType::CLIENT}}`)
            .then(response => response.json())
            .then(json => {
                if (json.data && json.data.length) {
                    clientSelect.addOptions(json.data); // Add the initial data to TomSelect
                }
            })
            .catch(error => {
                console.error("Error loading initial clients:", error);
            });
        // Driver Select2
       let driverSelect = new TomSelect('#driver-select', { // Initialize TomSelect for driver-select
            valueField: 'id',
            labelField: 'name',
            searchField: ['id', 'name', 'mobile'], // Allow search by ID, name or mobile
            load: function (query, callback) {
                fetch(`/users/search?q=${encodeURIComponent(query)}&user_type={{App\Enums\UserType::DRIVER}}`) // Adjust your route
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
                option: function (item, escape) {
                    return `
                    <div>
                        <strong>${escape(item.name)}</strong>
                        <div class="text-muted">ID: ${escape(item.id)}, Mobile: ${escape(item.mobile)}</div>
                    `;
                },
                item: function (item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });
        // Load initial data (all drivers)
        fetch(`/users/search?q=&user_type={{App\Enums\UserType::DRIVER}}`)
        .then(response => response.json())
        .then(json => {
            if (json.data && json.data.length) {
                driverSelect.addOptions(json.data); // Add the initial data to TomSelect
            }
        })
        .catch(error => {
            console.error("Error loading initial drivers:", error);
        });

    });
</script>
@endpush
@endsection