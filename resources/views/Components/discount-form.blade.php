<div class="discount-form-container card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tag me-2"></i>{{ __('messages.apply_discount') }}
        </h5>
    </div>
    <div class="card-body">
        <form id="discountForm" data-order-id="{{ $order->id }}">
            @csrf
            
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
                               step="0.01" 
                               min="0.01"
                               placeholder="0.00"
                               aria-label="Discount value">
                        <span class="input-group-text d-none" id="discountSuffix">%</span>
                    </div>
                    <div class="form-text" id="discountHelp">
                        {{ __('messages.order_subtotal') }}: ${{ number_format($order->sum_price, 2) }}
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
                <button type="submit" class="btn btn-primary" id="applyDiscountBtn">
                    <i class="fas fa-check me-1"></i>{{ __('messages.apply_discount') }}
                </button>
                
                @if($order->hasDiscount())
                <button type="button" class="btn btn-outline-danger" id="removeDiscountBtn">
                    <i class="fas fa-times me-1"></i>{{ __('messages.remove_discount') }}
                </button>
                @endif
                
                <button type="button" class="btn btn-outline-secondary" id="cancelDiscountBtn">
                    {{ __('messages.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('discountForm');
    const orderId = form.dataset.orderId;
    const typeFixed = document.getElementById('discountTypeFixed');
    const typePercentage = document.getElementById('discountTypePercentage');
    const valueInput = document.getElementById('discountValue');
    const prefix = document.getElementById('discountPrefix');
    const suffix = document.getElementById('discountSuffix');
    const preview = document.getElementById('discountPreview');
    const errors = document.getElementById('discountErrors');
    const applyBtn = document.getElementById('applyDiscountBtn');
    const removeBtn = document.getElementById('removeDiscountBtn');
    
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
        
        if (!value || value <= 0) {
            preview.classList.add('d-none');
            errors.classList.add('d-none');
            return;
        }
        
        validationTimeout = setTimeout(() => {
            fetch(`/api/orders/${orderId}/discount/validate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
                },
                body: JSON.stringify({ discount_type: type, discount_value: value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.valid) {
                    errors.classList.add('d-none');
                    preview.classList.remove('d-none');
                    document.getElementById('previewDiscountAmount').textContent = '$' + data.data.discount_amount;
                    document.getElementById('previewSubtotal').textContent = '$' + data.data.discounted_subtotal;
                    document.getElementById('previewTotal').textContent = '$' + data.data.new_total;
                    document.getElementById('previewSavings').textContent = '$' + data.data.savings;
                } else {
                    preview.classList.add('d-none');
                    errors.classList.remove('d-none');
                    errors.innerHTML = '<ul class="mb-0">' + 
                        (data.data?.errors || [data.message]).map(e => `<li>${e}</li>`).join('') + 
                        '</ul>';
                }
            })
            .catch(err => {
                console.error('Validation error:', err);
            });
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
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const type = typeFixed.checked ? 'fixed' : 'percentage';
        const value = parseFloat(valueInput.value);
        
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Applying...';
        
        fetch(`/api/orders/${orderId}/discount`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            },
            body: JSON.stringify({ discount_type: type, discount_value: value })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                errors.classList.remove('d-none');
                errors.textContent = data.message;
                applyBtn.disabled = false;
                applyBtn.innerHTML = '<i class="fas fa-check me-1"></i>Apply Discount';
            }
        })
        .catch(err => {
            errors.classList.remove('d-none');
            errors.textContent = 'An error occurred. Please try again.';
            applyBtn.disabled = false;
            applyBtn.innerHTML = '<i class="fas fa-check me-1"></i>Apply Discount';
        });
    });
    
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this discount?')) return;
            
            removeBtn.disabled = true;
            removeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Removing...';
            
            fetch(`/api/orders/${orderId}/discount`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                    removeBtn.disabled = false;
                    removeBtn.innerHTML = '<i class="fas fa-times me-1"></i>Remove Discount';
                }
            });
        });
    }
    
    updateInputDisplay();
    if (valueInput.value) {
        validateDiscount();
    }
});
</script>
