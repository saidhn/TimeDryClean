@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Breadcrumb / Back button -->
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}" style="color: #464687;">{{ __('messages.products') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                    </ol>
                </nav>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1"></i> {{ __('messages.back') }}
                </a>
            </div>

            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="row g-0">
                    <!-- Product Image Section -->
                    <div class="col-md-5 bg-light d-flex align-items-center justify-content-center p-4 border-end" style="min-height: 400px;">
                        @if ($product->image_path)
                            <div class="product-image-container w-100 h-100 d-flex align-items-center justify-content-center">
                                <img src="{{ asset('storage/' . $product->image_path) }}" 
                                     alt="{{ $product->name }}" 
                                     class="img-fluid rounded shadow-sm"
                                     style="max-height: 350px; object-fit: contain;">
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-tshirt fa-6x mb-3 opacity-25"></i>
                                <p class="mb-0">{{ __('messages.no_data_to_display') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Product Details Section -->
                    <div class="col-md-7">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-light text-muted mb-2 border">ID: #{{ $product->id }}</span>
                                    <h1 class="h2 fw-bold mb-0" style="color: #464687;">{{ $product->name }}</h1>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('products.edit', $product) }}"><i class="fas fa-edit me-2"></i>{{ __('messages.edit') }}</a></li>
                                        <li>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('{{ __('messages.confirm_deletion') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>{{ __('messages.delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <hr class="my-4 opacity-10">

                            <!-- Services Section -->
                            <div class="mb-4">
                                <h5 class="fw-bold mb-3 d-flex align-items-center">
                                    <i class="fas fa-concierge-bell me-2 text-primary" style="color: #464687 !important;"></i>
                                    {{ __('messages.service_pricing') }}
                                </h5>
                                
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 py-3 ps-4 text-muted small text-uppercase fw-bold">{{ __('messages.service') }}</th>
                                                <th class="border-0 py-3 pe-4 text-end text-muted small text-uppercase fw-bold">{{ __('messages.price') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($product->productServicePrices as $price)
                                                <tr>
                                                    <td class="py-3 ps-4 align-middle">
                                                        <div class="d-flex align-items-center">
                                                            <div class="service-icon me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; color: #464687;">
                                                                @php
                                                                    $icon = 'fas fa-check-circle';
                                                                    if (str_contains(strtolower($price->productService->name), 'wash')) $icon = 'fas fa-wind';
                                                                    if (str_contains(strtolower($price->productService->name), 'iron')) $icon = 'fas fa-plug'; // Iron icon replacement
                                                                @endphp
                                                                <i class="{{ $icon }} small"></i>
                                                            </div>
                                                            <span class="fw-medium text-dark">{{ $price->productService->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 pe-4 text-end align-middle">
                                                        <span class="fw-bold h5 mb-0" style="color: #464687;">{{ number_format((float) $price->price, 3) }}</span>
                                                        <span class="small text-muted ms-1">{{ __('messages.currency_symbol') }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="py-4 text-center text-muted italic">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        {{ __('messages.no_services_configured') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-5 d-flex gap-2">
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-primary px-4 rounded-pill" style="background-color: #464687; border-color: #464687;">
                                    <i class="fas fa-edit me-2"></i> {{ __('messages.modify') }}
                                </a>
                                <button onclick="window.print()" class="btn btn-light px-4 rounded-pill border">
                                    <i class="fas fa-print me-2"></i> {{ __('messages.print_invoice') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .breadcrumb-item + .breadcrumb-item::before {
        content: "\f105";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        font-size: 10px;
        color: #adb5bd;
    }
    
    .card-body {
        transition: all 0.3s ease;
    }

    .table tr {
        transition: background-color 0.2s ease;
    }

    .product-image-container img {
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .product-image-container:hover img {
        transform: scale(1.05);
    }

    @media print {
        .btn, .breadcrumb, .dropdown, hr {
            display: none !important;
        }
        .container {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        .col-md-5 {
            width: 30% !important;
        }
        .col-md-7 {
            width: 70% !important;
        }
    }
</style>
@endsection