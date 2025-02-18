<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.users.index') }}">
        {{ __('messages.users') }}
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('orders.index') }}">
        {{ __('messages.orders') }}
    </a>
</li>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.products') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
        <li><a class="dropdown-item" href="{{ route('products.index') }}">{{ __('messages.manage_products') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('product_services.index') }}">{{ __('messages.manage_product_services') }}</a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li></li>
    </ul>
</li>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.subscriptions') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
        <li><a class="dropdown-item" href="{{ route('subscriptions.index') }}">{{ __('messages.manage_subscriptions') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('client_subscriptions.index') }}">{{ __('messages.manage_client_subscriptions') }}</a></li>
    </ul>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('messages.logout') }}
    </a>
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>