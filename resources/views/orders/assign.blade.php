@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.order_assignment') }}</h1>

    <form class="row" action="{{ route('orders.assign') }}" method="POST">
        @csrf
        <div class="col-md-6">
            <div class="form-group">
                <label for="order-select">{{ __('messages.select_order') }}</label>
                <select id="order-select" name="order_id" class="form-control" required>
                    <option value="">{{ __('messages.search_order') }}</option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="driver-select">{{ __('messages.driver') }}</label>
                <select id="driver-select" name="driver_id"
                    class="form-control @error('driver_id') is-invalid @enderror" required>
                    <option value="">{{ __('messages.select_driver') }}</option>
                    @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ old('driver_id')==$driver->id ? 'selected' : '' }}>
                        {{ $driver->name }}
                    </option>
                    @endforeach
                </select>
                @error('driver_id')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
                <div id="recommendedDrivers" class="mt-2"></div>
                <button type="button" class="btn btn-sm btn-info mt-2" id="recommendDriver">{{ __('messages.recommend_driver') }}</button>

            </div>

        </div>
        <div class="mt-4"></div>
        <div class="col-md-6 ">
            <div class="form-group">
                <label class="d-block" for="bring_order">{{ __('messages.delivery') }}</label>
                <div class="form-check d-inline-block">
                    <input class="form-check-input" type="checkbox" name="bring_order" id="bring_order"
                        value="on" {{ old('bring_order') ? 'checked' : '' }}>

                    <label class="form-check-label" for="bring_order">
                        {{ __('messages.bring_order') }}
                    </label>
                </div>
                <div class="form-check d-inline-block">
                    <input class="form-check-input" type="checkbox" name="return_order" id="return_order" {{
                                    old('return_order') ? 'checked' : '' }}>
                    <label class="form-check-label" for="return_order">
                        {{ __('messages.return_order') }}
                    </label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="delivery_price">{{ __('messages.delivery_price') }}</label>
                <input type="number" name="delivery_price" id="delivery_price" class="form-control" value="{{ old('delivery_price') }}" min="0" required>
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
        <div class="mt-4"></div>
        
        <div class="col-md-2">
            <div class="form-group">
                <label for="street">{{ __('messages.street') }}</label>
                <input type="text" name="street" id="street" class="form-control" value="{{ old('street') }}" required>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="building">{{ __('messages.building') }}</label>
                <input type="text" name="building" id="building" class="form-control" value="{{ old('building') }}" required>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="floor">{{ __('messages.floor') }}</label>
                <input type="number" name="floor" id="floor" class="form-control" value="{{ old('floor') }}" required>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="apartment_number">{{ __('messages.appartment_number') }}</label>
                <input type="text" name="apartment_number" id="apartment_number" class="form-control" value="{{ old('apartment_number') }}" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3 mb-3">{{ __('messages.order_assignment') }}</button>
    </form>
</div>

<script>
    function setFocusAndShowRequired(inputId) {
        const inputElement = document.getElementById(inputId);

        if (inputElement) {
            inputElement.focus(); // Set focus on the input

            if (inputElement.hasAttribute('required')) {
            // Check if the input has the 'required' attribute

            if (inputElement.value.trim() === '') {
                // Check if the input is empty (after trimming whitespace)

                // Show the required message (using browser's built-in validation or custom message)
                inputElement.reportValidity(); // This will trigger the browser's default required message
                //Or you can make your own custom message:
                //inputElement.setCustomValidity("This field is required.");
                //inputElement.reportValidity();
                //inputElement.setCustomValidity(""); // Clear custom validity after showing
            }
            }
        } else {
            console.error(`Input element with ID '${inputId}' not found.`);
        }
        }
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#order-select', {
            valueField: 'id',
            labelField: 'display_name', // Define display_name in your data
            searchField: 'display_name',
            load: function(query, callback) {
                fetch(`/orders_search?q=${encodeURIComponent(query)}`) // Adjust your route
                    .then(response => response.json())
                    .then(json => {
                        if (json.data && json.data.length) {
                            console.log(json.data);
                            const formattedData = json.data.map(order => ({
                                id: order.id,
                                delivery: order.order_delivery,
                                display_name: `${order.id} - ${order.user.name} (${order.user.address.city.name})`,
                                user: order.user, // Add user object
                            }));

                            callback(formattedData);
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
                            <strong>${escape(item.display_name)}</strong>
                        </div>
                    `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.display_name)}</div>`;
                }
            },
            onChange: function(value) {
                if (value) {
                    // Find the selected order object
                    const selectedOrder = this.options[value];

                    if (selectedOrder) {
                        updateData(selectedOrder);
                    }
                }
            }
        });
        // Driver Select2
        new TomSelect('#driver-select', { // Initialize TomSelect for driver-select
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            load: function(query, callback) {
                fetch(`/users/search?q=${encodeURIComponent(query)}&user_type={{App\Enums\UserType::DRIVER}}`) // Adjust your route
                    .then(response => response.json())
                    .then(json => {
                        if (json.data && json.data.length) {
                            // console.log(json.data);
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
                        <div class="text-muted">ID: ${escape(item.id)}</div>  </div>
                    `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });
        document.getElementById('recommendDriver').addEventListener('click', function() {
    const orderId = document.getElementById('order-select').value;
    if (orderId) {
        fetch(`/orders_recommend/${orderId}/recommend-driver`)
            .then(response => response.json())
            .then(data => {
                const recommendedDriversDiv = document.getElementById('recommendedDrivers');
                recommendedDriversDiv.innerHTML = ''; // Clear previous recommendations

                if (data && data.length > 0) {
                    data.forEach(driver => {
                        const recommendButton = document.createElement('button');
                        recommendButton.textContent = `${driver.name} (ID: ${driver.id}, City: ${driver.address.city.name})`;
                        recommendButton.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'm-1'); // Add Bootstrap classes for styling
                        recommendButton.setAttribute('type','button');
                        recommendButton.addEventListener('click', function() {
                            const driverSelect = document.getElementById('driver-select').tomselect;
                            driverSelect.search(driver.name); // Start search
                            driverSelect.setValue(driver.id); // Set the selected value
                        });

                        recommendedDriversDiv.appendChild(recommendButton);
                    });
                } else {
                    recommendedDriversDiv.innerHTML = '<p>No drivers recommended.</p>';
                }
            });
    }else{
        setFocusAndShowRequired('order-select');
    }
});
    //address province and city select
    const citiesByProvince = @json(\App\Models\City:: select('id', 'name', 'province_id') -> get() -> groupBy('province_id'));

    const provinceSelect = document.getElementById('province_id');
    const citySelect = document.getElementById('city_id');
    
    function populateAddressFields(order) {
        const street = document.getElementById('street');
        const building = document.getElementById('building');
        const floor = document.getElementById('floor');
        const apartment_number = document.getElementById('apartment_number');

        if (street) {
            if (order.delivery && order.delivery.street) {
                street.value = order.delivery.street;
            } else if (order.user && order.user.address && order.user.address.street) {
                street.value = order.user.address.street;
            } else {
                street.value = ''; // Clear the field if no matching address found
            }
        }

        if (building) {
            if (order.delivery && order.delivery.building) {
                building.value = order.delivery.building;
            } else if (order.user && order.user.address && order.user.address.building) {
                building.value = order.user.address.building;
            } else {
                building.value = ''; // Clear the field if no matching address found
            }
        }

        if (floor) {
            if (order.delivery && order.delivery.floor) {
                floor.value = order.delivery.floor;
            } else if (order.user && order.user.address && order.user.address.floor) {
                floor.value = order.user.address.floor;
            } else {
                floor.value = ''; // Clear the field if no matching address found
            }
        }

        if (apartment_number) {
            if (order.delivery && order.delivery.apartment_number) {
                apartment_number.value = order.delivery.apartment_number;
            } else if (order.user && order.user.address && order.user.address.apartment_number) {
                apartment_number.value = order.user.address.apartment_number;
            } else {
                apartment_number.value = ''; // Clear the field if no matching address found
            }
        }
    }

    function updateData(order) {
        console.log(order);
        //update provice and city information
        const provinceSelect1 = document.getElementById('province_id');
        const citySelect1 = document.getElementById('city_id');

        if (order && order.user && order.user.address && order.user.address.city) {
            const provinceId = order.user.address.city.province_id;
            const cityId = order.user.address.city_id;

            if (provinceId !== undefined && cityId !== undefined) {
                console.log("Province ID:", provinceId);
                console.log("City ID:", cityId);

                // Update the province select
                if (provinceSelect1) {
                    provinceSelect1.value = provinceId;
                    updateAddress(provinceId);
                }

                // Update the city select
                if (citySelect1) {
                    // Clear existing options
                    citySelect1.value = cityId;

                    // Enable the city select
                    citySelect1.disabled = false;
                }
            } else {
                console.log("Province ID or City ID is undefined.");
            }
        } else {
            console.log("Address or city data is missing from the order.");
        }
        //update delivery_price field
        const delivery_price = document.getElementById('delivery_price');
        if(delivery_price && order.delivery){
            delivery_price.value = order.delivery.price;
        }
        //update delivery direction
        const return_order = document.getElementById('return_order');
        const bring_order = document.getElementById('bring_order');
        if(order.delivery && order.delivery.direction){
            bring_order.checked = (order.delivery.direction == '{{ App\Enums\DeliveryDirection::ORDER_TO_WORK }}' || order.delivery.direction == '{{ App\Enums\DeliveryDirection::BOTH }}');
            return_order.checked = (order.delivery.direction == '{{ App\Enums\DeliveryDirection::WORK_TO_ORDER }}' || order.delivery.direction == '{{ App\Enums\DeliveryDirection::BOTH }}');
        }
        //update address fields
        populateAddressFields(order);

        // Update driver select
        if(order.delivery && order.delivery.driver ){
            const driverSelect = document.getElementById('driver-select').tomselect;
                            driverSelect.search(order.delivery.driver.name); // Start search
                            driverSelect.setValue(order.delivery.driver.id);
        }
    }

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

        // Update delivery price if needed
        const delivery_price = document.getElementById('delivery_price');

        function updateDeliveryPrice() {
            if (delivery_price.value === '' || delivery_price.value === '0' || delivery_price.value === '1' || delivery_price.value === '2') {    

                if (return_order.checked && bring_order.checked) {
                    delivery_price.value = 2;
                }else
                if (return_order.checked || bring_order.checked) {
                    delivery_price.value = 1;
                }else{
                    delivery_price.value = 0;
                }
            }
        }

        return_order.addEventListener('change', updateDeliveryPrice);
        bring_order.addEventListener('change', updateDeliveryPrice);
    });
</script>
@endsection