@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        {{ __('messages.create_product') }}
                    </h5>
                </div>

                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" id="productForm">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        {{ __('messages.name') }}
                                    </label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                    @error('name')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image" class="form-label fw-bold">
                                        <i class="fas fa-image me-1"></i>
                                        {{ __('messages.image') }}
                                    </label>
                                    <input id="image" type="file" class="form-control @error('image') is-invalid @enderror" 
                                           name="image" accept="image/*">
                                    @error('image')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    {{ __('messages.service_pricing') }}
                                </h6>
                                <span class="badge bg-secondary" data-bs-toggle="tooltip" 
                                      title="{{ __('messages.service_pricing_help') }}">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                            </div>

                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('messages.service_pricing_info') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>

                            <div class="row" id="servicePrices">
                                @foreach($services as $service)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 border-light shadow-sm">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input service-checkbox" type="checkbox" 
                                                       id="service_{{ $service->id }}" 
                                                       name="services[{{ $service->id }}][enabled]"
                                                       value="1"
                                                       {{ old("services.{$service->id}.enabled") ? 'checked' : '' }}
                                                       onchange="toggleServicePrice({{ $service->id }})">
                                                <label class="form-check-label fw-bold" for="service_{{ $service->id }}">
                                                    {{ $service->name }}
                                                </label>
                                            </div>
                                            
                                            <div class="input-group input-group-sm" id="price_group_{{ $service->id }}" 
                                                 style="{{ old("services.{$service->id}.enabled") ? 'display: flex;' : 'display: none;' }}">
                                                <span class="input-group-text">KWD</span>
                                                <input type="number" 
                                                       class="form-control price-input" 
                                                       id="price_{{ $service->id }}"
                                                       name="services[{{ $service->id }}][price]"
                                                       value="{{ old("services.{$service->id}.price") }}"
                                                       step="0.001" 
                                                       min="0" 
                                                       max="9999.999"
                                                       placeholder="0.000">
                                            </div>
                                            
                                            <div class="invalid-feedback d-block" id="error_{{ $service->id }}"></div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            @if($services->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ __('messages.no_services') }}
                            </div>
                            @endif
                        </div>

                        <div class="border-top pt-4 mt-4">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    {{ __('messages.cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    {{ __('messages.create') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleServicePrice(serviceId) {
    const checkbox = document.getElementById(`service_${serviceId}`);
    const priceGroup = document.getElementById(`price_group_${serviceId}`);
    const priceInput = document.getElementById(`price_${serviceId}`);
    
    if (checkbox.checked) {
        priceGroup.style.display = 'flex';
        priceInput.focus();
    } else {
        priceGroup.style.display = 'none';
        priceInput.value = '';
    }
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    const enabledServices = document.querySelectorAll('.service-checkbox:checked');
    let isValid = true;
    
    enabledServices.forEach(checkbox => {
        const serviceId = checkbox.id.replace('service_', '');
        const priceInput = document.getElementById(`price_${serviceId}`);
        const errorDiv = document.getElementById(`error_${serviceId}`);
        
        if (!priceInput.value || parseFloat(priceInput.value) < 0) {
            priceInput.classList.add('is-invalid');
            errorDiv.textContent = '{{ __("messages.price_required") }}';
            errorDiv.style.display = 'block';
            isValid = false;
        } else {
            priceInput.classList.remove('is-invalid');
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
        }
    });
    
    if (!isValid) {
        e.preventDefault();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection