@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-heart me-2"></i>{{ __('messages.assign_points') }}</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('points.assign') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="user_id" class="form-label fw-bold">{{ __('messages.user') }}</label>
                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required></select>
                            @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="points_package_id" class="form-label fw-bold">{{ __('messages.points_packages') }}</label>
                            <select name="points_package_id" id="points_package_id" class="form-select @error('points_package_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_package') }}</option>
                                @foreach ($packages as $package)
                                <option value="{{ $package->id }}" {{ old('points_package_id') == $package->id ? 'selected' : '' }}>
                                    {{ $package->name }} — {{ number_format($package->points, 0) }} pts
                                </option>
                                @endforeach
                            </select>
                            @error('points_package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('points.history') }}" class="btn btn-secondary">
                                <i class="fas fa-history me-1"></i>{{ __('messages.points_purchase_history') }}
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i>{{ __('messages.assign_points') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let clientSelect = new TomSelect('#user_id', {
        valueField: 'id',
        labelField: 'name',
        searchField: ['id', 'name', 'mobile'],
        load: function (query, callback) {
            fetch(`/users/search?q=${encodeURIComponent(query)}&user_type={{ App\Enums\UserType::CLIENT }}`)
                .then(response => response.json())
                .then(json => {
                    if (json.data && json.data.length) {
                        callback(json.data);
                    } else {
                        callback([]);
                    }
                })
                .catch(() => callback([]));
        },
        render: {
            option: function (item, escape) {
                return `<div>
                    <strong>${escape(item.name)}</strong>
                    <div class="text-muted">ID: ${escape(String(item.id))}, Mobile: ${escape(item.mobile)}</div>
                </div>`;
            },
            item: function (item, escape) {
                return `<div>${escape(item.name)}</div>`;
            }
        }
    });

    // Pre-load initial list
    fetch(`/users/search?q=&user_type={{ App\Enums\UserType::CLIENT }}`)
        .then(response => response.json())
        .then(json => {
            if (json.data && json.data.length) {
                clientSelect.addOptions(json.data);
            }
            @if(old('user_id'))
            const oldUserId = '{{ old('user_id') }}';
            const existing = json.data ? json.data.find(u => String(u.id) === String(oldUserId)) : null;
            if (existing) {
                clientSelect.addOption(existing);
                clientSelect.setValue(oldUserId, true);
            } else {
                fetch(`/users/search?q=${encodeURIComponent(oldUserId)}&user_type={{ App\Enums\UserType::CLIENT }}`)
                    .then(r => r.json())
                    .then(json2 => {
                        if (json2.data && json2.data.length) {
                            const user = json2.data.find(u => String(u.id) === String(oldUserId));
                            if (user) {
                                clientSelect.addOption(user);
                                clientSelect.setValue(oldUserId, true);
                            }
                        }
                    });
            }
            @endif
        })
        .catch(error => console.error('Error loading clients:', error));
});
</script>
@endpush
