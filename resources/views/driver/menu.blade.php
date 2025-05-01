<li class="nav-item">
    <a class="nav-link" href="{{ route('driver.delivery') }}">
        {{ __('messages.current_orders') }}
    </a>
</li><li class="nav-item">
    <a class="nav-link" href="{{ route('driver.deliveryHistory') }}">
        {{ __('messages.delivery_history') }}
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
    <a class="nav-link" href="{{ route('driver.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('messages.logout') }}
    </a>
    <form id="logout-form" action="{{ route('driver.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>