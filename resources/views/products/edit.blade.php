@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        {{ __('messages.edit') }} {{ __('messages.product') }}: {{ $product->name }}
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

                    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" id="productForm">
                        @csrf
                        @method('PUT')

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        {{ __('messages.name') }}
                                    </label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" value="{{ old('name', $product->name) }}" required autocomplete="name" autofocus>
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
                                    @if ($product->image_path)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-height: 100px;">
                                        <button type="button" class="btn btn-danger btn-sm ms-2" onclick="document.getElementById('delete-image-form').submit();">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    @endif
                                    <input id="image" type="file" class="form-control @error('image') is-invalid @enderror" name="image" accept="image/*">
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
                                <span class="badge bg-info">
                                    {{ $product->productServicePrices->count() }} {{ __('messages.services_configured') }}
                                </span>
                            </div>

                            <div class="row" id="servicePrices">
                                @foreach($services as $service)
                                @php
                                    $existingPrice = $productServicePrices->get($service->id);
                                    $isEnabled = old("services.{$service->id}.enabled", $existingPrice ? true : false);
                                    $priceValue = old("services.{$service->id}.price", $existingPrice ? $existingPrice->price : '');
                                @endphp
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 {{ $existingPrice ? 'border-success' : 'border-light' }} shadow-sm">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input service-checkbox" type="checkbox" 
                                                       id="service_{{ $service->id }}" 
                                                       name="services[{{ $service->id }}][enabled]"
                                                       value="1"
                                                       {{ $isEnabled ? 'checked' : '' }}
                                                       onchange="toggleServicePrice({{ $service->id }})">
                                                <label class="form-check-label fw-bold" for="service_{{ $service->id }}">
                                                    {{ $service->name }}
                                                    @if($existingPrice)
                                                    <i class="fas fa-check-circle text-success ms-1"></i>
                                                    @endif
                                                </label>
                                            </div>
                                            
                                            <div class="input-group input-group-sm" id="price_group_{{ $service->id }}" 
                                                 style="{{ $isEnabled ? 'display: flex;' : 'display: none;' }}">
                                                <span class="input-group-text">KWD</span>
                                                <input type="number" 
                                                       class="form-control price-input" 
                                                       id="price_{{ $service->id }}"
                                                       name="services[{{ $service->id }}][price]"
                                                       value="{{ $priceValue }}"
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
                                <button type="submit" class="btn btn-warning" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    {{ __('messages.update') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    @if ($product->image_path)
                    <form id="delete-image-form" action="{{ route('products.image.destroy', $product) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
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
    const card = checkbox.closest('.card');
    
    if (checkbox.checked) {
        priceGroup.style.display = 'flex';
        card.classList.remove('border-light');
        card.classList.add('border-success');
        priceInput.focus();
    } else {
        priceGroup.style.display = 'none';
        card.classList.remove('border-success');
        card.classList.add('border-light');
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
</script>
@endsection