@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ __('messages.modify') }} <strong>{{$user->user_type_translated()}}</strong>
                </div>

                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('messages.name') }}</label><span
                                class="text-danger"> *</span>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ $user->name ?? old('name') }}" required autocomplete="name"
                                autofocus>
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('messages.email') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ $user->email ?? old('email') }}" autocomplete="email">
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">{{ __('messages.mobile_no') }}</label><span
                                class="text-danger"> *</span>
                            <input id="mobile" type="text" class="form-control @error('mobile') is-invalid @enderror"
                                name="mobile" value="{{ $user->mobile ?? old('mobile') }}" required
                                autocomplete="mobile">
                            @error('mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="province_id" class="form-label">{{ __('messages.province') }}</label><span
                                class="text-danger"> *</span>
                            <select id="province_id" class="form-control @error('province_id') is-invalid @enderror"
                                name="province_id" required>
                                <option value="">{{__('messages.select_province')}}</option>
                                @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" {{$province->id == $user->address->province_id?'
                                    selected':''}}>{{ $province->name }}</option>
                                @endforeach
                            </select>
                            @error('province_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="city_id" class="form-label">{{ __('messages.city') }}</label><span
                                class="text-danger"> *</span>
                            <select id="city_id" class="form-control @error('city_id') is-invalid @enderror"
                                name="city_id" required disabled>
                                <option value="">{{__('messages.select_city')}}</option>
                            </select>
                            @error('city_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <button type="submit" class="btn btn-primary">
                                {{ __('messages.modify') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const citiesByProvince = @json(\App\Models\City:: select('id', 'name', 'province_id') -> get() -> groupBy('province_id'));

    const provinceSelect = document.getElementById('province_id');
    const citySelect = document.getElementById('city_id');

    provinceSelect.addEventListener('change', function () {
        updateAddress(this.value);
    });

    function updateAddress(val) {
        citySelect.innerHTML = '<option value="">{{__('messages.select_city ')}}</option>'; // Clear existing options
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

    updateAddress(provinceSelect.value);
    citySelect.value = {{ $user -> address -> city_id }};
</script>
@endsection