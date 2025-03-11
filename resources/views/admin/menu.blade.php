<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.users.index') }}">
        {{ __('messages.users') }}
    </a>
</li>
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

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown2" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.products') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown2">
        <li><a class="dropdown-item" href="{{ route('products.index') }}">{{ __('messages.manage_products') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('product_services.index') }}">{{ __('messages.manage_product_services') }}</a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li></li>
    </ul>
</li>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown3" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.subscriptions') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown3">
        <li><a class="dropdown-item" href="{{ route('subscriptions.index') }}">{{ __('messages.manage_subscriptions') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('client_subscriptions.index') }}">{{ __('messages.manage_client_subscriptions') }}</a></li>
    </ul>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown4" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.contact_messages') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown4">
        <li><a class="dropdown-item" href="{{ route('admin.contacts.index') }}">{{ __('messages.contact_messages') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('contact.show') }}">{{ __('messages.send_message') }}</a></li>
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