<div class="discount-form-container card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tag me-2"></i>{{ __('messages.apply_discount') }}
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.discount_type') }}</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="discount_type" id="discountTypeFixed" value="fixed" 
                           {{ $order->discount_type === 'fixed' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary" for="discountTypeFixed">
                        <i class="fas fa-dollar-sign"></i> {{ __('messages.fixed_amount') }}
                    </label>
                    
                    <input type="radio" class="btn-check" name="discount_type" id="discountTypePercentage" value="percentage"
                           {{ $order->discount_type === 'percentage' ? 'checked' : '' }}>
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
                           value="{{ $order->discount_value ?? '' }}"
                           step="1" 
                           min="1"
                           placeholder="0.00"
                           aria-label="Discount value">
                    <span class="input-group-text d-none" id="discountSuffix">%</span>
                </div>
                <div class="form-text" id="discountHelp">
                    @php
                        $originalSubtotal = $order->hasDiscount() 
                            ? $order->sum_price + $order->discount_amount 
                            : $order->sum_price;
                        $existingDiscount = $order->discount_amount ?? 0;
                    @endphp
                    {{ __('messages.order_subtotal') }}: $<span id="currentSubtotal" 
                        data-value="{{ number_format($originalSubtotal, 2, '.', '') }}"
                        data-discount="{{ number_format($existingDiscount, 2, '.', '') }}">{{ number_format($originalSubtotal, 2) }}</span>
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
        
        @if($order->hasDiscount())
        <div class="alert alert-success">
            <strong>{{ __('messages.discount_applied') }}:</strong>
            {{ $order->discount_display }}
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="clearDiscountBtn">
                <i class="fas fa-times me-1"></i>{{ __('messages.remove_discount') }}
            </button>
        </div>
        @endif
        
        <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            {{ __('messages.discount') }} {{ __('messages.save') }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
        const isPercentage = typePercentage && typePercentage.checked;
        if (prefix) prefix.classList.toggle('d-none', isPercentage);
        if (suffix) suffix.classList.toggle('d-none', !isPercentage);
        if (valueInput) {
            valueInput.placeholder = isPercentage ? '0.00' : '0.00';
            valueInput.max = isPercentage ? '100' : '999999.99';
        }
    }
    
    function validateDiscount() {
        if (!typeFixed || !typePercentage || !valueInput) return;
        
        clearTimeout(validationTimeout);
        
        const type = typeFixed.checked ? 'fixed' : (typePercentage.checked ? 'percentage' : null);
        const value = parseFloat(valueInput.value);
        const subtotalText = currentSubtotalSpan ? currentSubtotalSpan.getAttribute('data-value') : '0';
        const subtotal = parseFloat(subtotalText) || 0;
        
        if (!type || !value || value <= 0) {
            if (preview) preview.classList.add('d-none');
            if (errors) errors.classList.add('d-none');
            return;
        }
        
        validationTimeout = setTimeout(() => {
            let discountAmount = 0;
            let isValid = true;
            let errorMessages = [];
            
            if (type === 'fixed') {
                if (value > subtotal) {
                    isValid = false;
                    errorMessages.push(@json(__('messages.discount_validation_exceeds_subtotal')));
                } else {
                    discountAmount = value;
                }
            } else if (type === 'percentage') {
                if (value > 100) {
                    isValid = false;
                    errorMessages.push(@json(__('messages.discount_validation_exceeds_100_percent')));
                } else {
                    discountAmount = subtotal * (value / 100);
                }
            }
            
            if (isValid) {
                if (errors) errors.classList.add('d-none');
                if (preview) preview.classList.remove('d-none');
                
                const discountedSubtotal = subtotal - discountAmount;
                
                const currencySymbol = @json(__('messages.currency_symbol'));
                document.getElementById('previewDiscountAmount').textContent = currencySymbol + ' ' + discountAmount.toFixed(2);
                document.getElementById('previewSubtotal').textContent = currencySymbol + ' ' + discountedSubtotal.toFixed(2);
                document.getElementById('previewTotal').textContent = currencySymbol + ' ' + discountedSubtotal.toFixed(2);
                document.getElementById('previewSavings').textContent = currencySymbol + ' ' + discountAmount.toFixed(2);
            } else {
                if (preview) preview.classList.add('d-none');
                if (errors) {
                    errors.classList.remove('d-none');
                    errors.innerHTML = '<ul class="mb-0">' + 
                        errorMessages.map(e => `<li>${e}</li>`).join('') + 
                        '</ul>';
                }
            }
        }, 500);
    }
    
    if (typeFixed) {
        typeFixed.addEventListener('change', () => {
            updateInputDisplay();
            validateDiscount();
        });
    }
    
    if (typePercentage) {
        typePercentage.addEventListener('change', () => {
            updateInputDisplay();
            validateDiscount();
        });
    }
    
    if (valueInput) {
        valueInput.addEventListener('input', validateDiscount);
    }
    
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (typeFixed) typeFixed.checked = false;
            if (typePercentage) typePercentage.checked = false;
            if (valueInput) valueInput.value = '';
            if (preview) preview.classList.add('d-none');
            if (errors) errors.classList.add('d-none');
            updateInputDisplay();
        });
    }
    
    updateInputDisplay();
    if (valueInput && valueInput.value) {
        validateDiscount();
    }
});
</script>
