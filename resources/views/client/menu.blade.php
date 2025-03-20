<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="{{ route('orders.index') }}" id="navbarDropdown1" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.orders') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown1">
        <li><a class="dropdown-item" href="{{ route('orders.index') }}">{{ __('messages.manage_orders') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('orders.assign.form') }}">{{ __('messages.order_assignment') }}</a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li class="text-center">
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{__('messages.create_order')}}</a>
        </li>
    </ul>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('client.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('messages.logout') }}
    </a>
    <form id="logout-form" action="{{ route('client.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>