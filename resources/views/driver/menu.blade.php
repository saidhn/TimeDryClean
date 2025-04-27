<li class="nav-item">
    <a class="nav-link" href="{{ route('driver.delivery') }}">
        {{ __('messages.delivery_orders') }}
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('driver.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('messages.logout') }}
    </a>
    <form id="logout-form" action="{{ route('driver.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>