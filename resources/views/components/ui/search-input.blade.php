@props([
    'placeholder' => 'Search...',
    'value' => '',
    'showClear' => true
])

@php
$inputId = $attributes->get('id', 'search-' . uniqid());
@endphp

<div class="position-relative">
    <div class="position-absolute start-0 top-50 translate-middle-y ps-3">
        <i class="fas fa-search text-muted"></i>
    </div>
    
    <input 
        type="text" 
        id="{{ $inputId }}"
        class="form-control ps-5 pe-5 search-input" 
        placeholder="{{ $placeholder }}"
        value="{{ $value }}"
        {{ $attributes }}
    />
    
    @if($showClear)
        <button 
            type="button" 
            class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-1 p-1 clear-search d-none"
            aria-label="Clear search"
        >
            <i class="fas fa-times text-muted"></i>
        </button>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('{{ $inputId }}');
    const clearBtn = searchInput?.parentElement.querySelector('.clear-search');
    
    if (searchInput && clearBtn) {
        // Show/hide clear button
        function toggleClearButton() {
            if (searchInput.value) {
                clearBtn.classList.remove('d-none');
            } else {
                clearBtn.classList.add('d-none');
            }
        }
        
        searchInput.addEventListener('input', toggleClearButton);
        toggleClearButton();
        
        // Clear search
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            toggleClearButton();
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }
});
</script>
@endpush
