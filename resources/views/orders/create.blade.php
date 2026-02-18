@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.create_order') }}</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="create-order-form" action="{{ route('orders.store') }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-body">
            @if(Auth::guard('admin')->check() || Auth::guard('employee')->check() || Auth::guard('driver')->check())
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user-select">{{ __('messages.user') }}</label>
                            <select id="user-select" name="user_id"
                                    class="form-control @error('user_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_user') }}</option>
                            </select>
                            <div id="user-select-error" class="invalid-feedback d-block">
                                @error('user_id')
                                    <strong>{{ $message }}</strong>
                                @enderror
                            </div>
                        </div>
                    </div>
                    @else
                        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    @endif
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
                                1); $i++)
                                <tr>
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
                                        </select>
                                        <small class="text-muted service-hint d-none">
                                            <i class="fas fa-info-circle"></i> {{ __('messages.select_product') }}
                                        </small>
                                        <small class="text-warning no-services-warning d-none">
                                            <i class="fas fa-exclamation-triangle"></i> {{ __('messages.no_services_configured') }}
                                        </small>
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

            <button type="button" id="show_hide_deliveryOpts" class="btn btn-outline-primary d-block mb-3">
                {{ __('messages.delivery_options') }}
            </button>
                <div class="row" id="deliveryOpts" style="display: none">
                    

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
                    @if(Auth::guard('admin')->check() || Auth::guard('employee')->check() || Auth::guard('driver')->check())

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-check-label" for="delivery_price">{{ __('messages.delivery_price')
                                }}</label>
                            <input type="number" min="0" class="form-control" id="delivery_price" name="delivery_price"
                                   value="{{ old('delivery_price', 0) }}">
                            <div id="price-warning" class="text-danger small mt-1" style="display: none;">
                                <i class="fa fa-exclamation-triangle"></i> {{ __('messages.delivery_price_zero_warning') }}
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-5">
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

                <div class="discount-form-container card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tag me-2"></i>{{ __('messages.apply_discount') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="discountFormCreate">
                            @csrf
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('messages.discount_type') }}</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="discount_type" id="discountTypeFixed" value="fixed">
                                        <label class="btn btn-outline-primary" for="discountTypeFixed">
                                            <i class="fas fa-dollar-sign"></i> {{ __('messages.fixed_amount') }}
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="discount_type" id="discountTypePercentage" value="percentage">
                                        <label class="btn btn-outline-primary" for="discountTypePercentage">
                                            <i class="fas fa-percent"></i> {{ __('messages.percentage') }}
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="discountValue" class="form-label">
                                        <span id="discountValueLabel">{{ __('messages.discount_value') }}</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="discountPrefix">$</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="discountValue" 
                                               name="discount_value"
                                               value=""
                                               step="0.01" 
                                               min="0.01"
                                               placeholder="0.00"
                                               aria-label="Discount value">
                                        <span class="input-group-text d-none" id="discountSuffix">%</span>
                                    </div>
                                    <div class="form-text" id="discountHelp">
                                        {{ __('messages.order_subtotal') }}: $<span id="currentSubtotal">0.00</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="discountPreview" class="alert alert-info d-none mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ __('messages.discount_preview') }}:</strong>
                                        <div class="mt-1">
                                            <small>{{ __('messages.discount_amount_calculated') }}: <span id="previewDiscountAmount">$0.00</span></small><br>
                                            <small>{{ __('messages.new_subtotal') }}: <span id="previewSubtotal">$0.00</span></small><br>
                                            <small>{{ __('messages.new_total') }}: <span id="previewTotal">$0.00</span></small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success fs-6">
                                            {{ __('messages.save') }} <span id="previewSavings">$0.00</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="discountErrors" class="alert alert-danger d-none"></div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="clearDiscountBtn">
                                    {{ __('messages.clear_discount') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            </div>
        </div>
    </form>
</div>


<style>
    .ts-wrapper.is-invalid .ts-control {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let totalPrice = 0;
        let updateTotal;
        
        $(document).ready(function () {

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

            updateTotal = function() {
                totalPrice = 0;
                $('#order-product-services tr').each(function () {
                    totalPrice += calculateRowPrice($(this));
                });

                if (bringOrderCheckbox.checked || returnOrderCheckbox.checked) {
                    const deliveryPrice = parseFloat(deliveryPriceInput.value) || 0;
                    totalPrice += deliveryPrice;
                }

                $('#total-price-display').text(totalPrice.toFixed(2));
            };

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

            // Function to load services for a product via AJAX
            function loadProductServices(productId, serviceSelect, callback) {
                const row = serviceSelect.closest('tr');
                const hint = row.find('.service-hint');
                const warning = row.find('.no-services-warning');
                
                serviceSelect.html('<option value="">{{ __("messages.choose_service") }}</option>');
                hint.addClass('d-none');
                warning.addClass('d-none');
                
                if (!productId) {
                    hint.removeClass('d-none');
                    return;
                }
                
                fetch(`/api/products/${productId}/services`, {
                        credentials: 'include'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.data.services.length === 0) {
                            warning.removeClass('d-none');
                        } else {
                            data.data.services.forEach(service => {
                                const option = $('<option></option>')
                                    .val(service.id)
                                    .text(`${service.name} - ${service.price} KWD`)
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

            // Handle product selection change
            $(document).on('change', '.product-select', function() {
                const productId = $(this).val();
                const serviceSelect = $(this).closest('tr').find('.product-service-select');
                loadProductServices(productId, serviceSelect);
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
                        <option value="">{{ __('messages.choose_service') }}</option>
                    </select>
                    <small class="text-muted service-hint">
                        <i class="fas fa-info-circle"></i> {{ __('messages.select_product') }}
                    </small>
                    <small class="text-warning no-services-warning d-none">
                        <i class="fas fa-exclamation-triangle"></i> {{ __('messages.no_services_configured') }}
                    </small>
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
                updateTotal();
            });

            $(document).on('click', '.remove-row', function () {
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
                driverSelect.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                deliveryPriceInput.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                province_id.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                city_id.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                street.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                building.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                floor.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);
                apartment_number.required = (bringOrderCheckbox.checked || returnOrderCheckbox.checked);

                driverSelect.setCustomValidity("");
                validateDriverAndDelivery();
            }

            // Clear driver button functionality
            clearDriverBtn.addEventListener('click', function() {
                // Clear TomSelect if available
                if (typeof driverSelectTS !== 'undefined' && driverSelectTS) {
                    driverSelectTS.clear();
                } else {
                    driverSelect.value = '';
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

            form.addEventListener('submit', function (event) {
                toggleDriverRequired();
                let hasError = false;

                const userSelectEl = document.getElementById('user-select');
                const userErrorEl = document.getElementById('user-select-error');
                if (userSelectEl && userErrorEl && !userSelectEl.value) {
                    userErrorEl.innerHTML = '<strong>{{ __('messages.select_user') }}</strong>';
                    userSelectEl.closest('.ts-wrapper') && userSelectEl.closest('.ts-wrapper').classList.add('is-invalid');
                    hasError = true;
                } else if (userSelectEl && userErrorEl) {
                    userErrorEl.innerHTML = '';
                    userSelectEl.closest('.ts-wrapper') && userSelectEl.closest('.ts-wrapper').classList.remove('is-invalid');
                }

                if (!validateDriverAndDelivery()) {
                    hasError = true;
                    driverSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                if (hasError) {
                    event.preventDefault();
                    if (userSelectEl && !userSelectEl.value) {
                        document.getElementById('user-select-error').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
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

        // Discount form functionality for create page
        const discountForm = document.getElementById('discountFormCreate');
        const typeFixed = document.getElementById('discountTypeFixed');
        const typePercentage = document.getElementById('discountTypePercentage');
        const valueInput = document.getElementById('discountValue');
        const prefix = document.getElementById('discountPrefix');
        const suffix = document.getElementById('discountSuffix');
        const preview = document.getElementById('discountPreview');
        const errors = document.getElementById('discountErrors');
        const clearBtn = document.getElementById('clearDiscountBtn');
        const currentSubtotalSpan = document.getElementById('currentSubtotal');
        
        let validationTimeout;
        
        function updateInputDisplay() {
            const isPercentage = typePercentage.checked;
            prefix.classList.toggle('d-none', isPercentage);
            suffix.classList.toggle('d-none', !isPercentage);
            valueInput.placeholder = isPercentage ? '0.00' : '0.00';
            valueInput.max = isPercentage ? '100' : '999999.99';
        }
        
        function validateDiscount() {
            clearTimeout(validationTimeout);
            
            const type = typeFixed.checked ? 'fixed' : 'percentage';
            const value = parseFloat(valueInput.value);
            const subtotal = parseFloat(totalPrice.toFixed(2));
            
            currentSubtotalSpan.textContent = subtotal.toFixed(2);
            
            if (!value || value <= 0) {
                preview.classList.add('d-none');
                errors.classList.add('d-none');
                return;
            }
            
            validationTimeout = setTimeout(() => {
                let discountAmount = 0;
                let isValid = true;
                let errorMessages = [];
                
                if (type === 'fixed') {
                    if (value > subtotal) {
                        isValid = false;
                        errorMessages.push('Fixed discount cannot exceed order subtotal');
                    } else {
                        discountAmount = value;
                    }
                } else {
                    if (value > 100) {
                        isValid = false;
                        errorMessages.push('Percentage discount cannot exceed 100%');
                    } else {
                        discountAmount = subtotal * (value / 100);
                    }
                }
                
                if (isValid) {
                    errors.classList.add('d-none');
                    preview.classList.remove('d-none');
                }
            }, 500);
        }
        
        typeFixed.addEventListener('change', () => {
            updateInputDisplay();
            validateDiscount();
        });
        
        typePercentage.addEventListener('change', () => {
            updateInputDisplay();
            validateDiscount();
        });
        
        valueInput.addEventListener('input', validateDiscount);
        
        clearBtn.addEventListener('click', function() {
            typeFixed.checked = false;
            typePercentage.checked = false;
            valueInput.value = '';
            preview.classList.add('d-none');
            errors.classList.add('d-none');
            updateInputDisplay();
        });
        
        // Update discount validation when order total changes
        const originalUpdateTotal = updateTotal;
        updateTotal = function() {
            originalUpdateTotal();
            validateDiscount();
        };
        
        updateInputDisplay();
    });
</script>


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // show/hide delivery options
        $('#show_hide_deliveryOpts').on('click',function(){
            const ds = $('#deliveryOpts').css('display');
                if(ds != 'none'){
                    $('#deliveryOpts').slideUp(500);
                }else{
                    $('#deliveryOpts').slideDown(500).fadeIn(500);
                }
            });
        @if(Auth::guard('admin')->check() || Auth::guard('employee')->check() || Auth::guard('driver')->check())
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
                    clientSelect.addOptions(json.data);
                }
                @if(old('user_id'))
                const oldUserId = '{{ old('user_id') }}';
                fetch(`/users/search?q=${encodeURIComponent(oldUserId)}&user_type={{App\Enums\UserType::CLIENT}}`)
                    .then(r => r.json())
                    .then(json => {
                        if (json.data && json.data.length) {
                            const user = json.data.find(u => String(u.id) === String(oldUserId));
                            if (user) {
                                clientSelect.addOption(user);
                                clientSelect.setValue(oldUserId, true);
                            }
                        }
                    });
                @endif
            })
            .catch(error => {
                console.error("Error loading initial clients:", error);
            });
        @endif
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
                        </div>
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