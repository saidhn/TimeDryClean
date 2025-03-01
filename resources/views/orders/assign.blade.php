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
                    class="form-control @error('driver_id') is-invalid @enderror">
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
                <button type="button" class="btn btn-sm btn-info mt-2" id="recommendDriver">Recommend Driver</button>

            </div>

        </div>
        <div class="mt-4"></div>
        <div class="col-md-12 d-flex align-items-center">
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
        <div class="mt-4"></div>
        <div class="col-md-2">
            <div class="form-group">
                <label for="delivery_price">{{ __('messages.delivery_price') }}</label>
                <input type="number" name="delivery_price" id="delivery_price" class="form-control" value="{{ old('delivery_price') }}" min="0" required>
            </div>
        </div>

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
                            const formattedData = json.data.map(order => ({
                                id: order.id,
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
                            console.log(json.data);
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
    }
});
    });
</script>
@endsection