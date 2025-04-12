<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="{{ route('orders.index') }}" id="navbarDropdown1" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.orders') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown1">
        <li><a class="dropdown-item" href="{{ route('orders.index') }}">{{ __('messages.manage_orders') }}</a></li>
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
    <a class="nav-link dropdown-toggle" href="{{ route('client.clientSubscription.index') }}" id="navbarDropdown1" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.subscription') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown1">
        <li><a class="dropdown-item" href="{{ route('client.clientSubscription.index') }}">{{ __('messages.subscription') }}</a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li class="text-center">
            <a href="{{ route('client.clientSubscription.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{__('messages.add_balance')}}</a>
        </li>
    </ul>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('client.bills.index') }}">
        {{ __('messages.bills') }}
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('client.balance.index') }}">
        {{ __('messages.balance') }}
    </a>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown4" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ __('messages.contact_messages') }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarDropdown4">
        <li><a class="dropdown-item" href="{{ route('contact.index') }}">{{ __('messages.contact_messages') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('contact.showForm') }}">{{ __('messages.send_message') }}</a></li>
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