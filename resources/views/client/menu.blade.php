<li class="nav-item">
    <a class="nav-link" href="{{ route('client.orders.index') }}">My Orders</a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('client.profile.edit') }}">Profile</a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('client.settings') }}">Settings</a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('client.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('Logout') }}
    </a>
    <form id="logout-form" action="{{ route('client.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>