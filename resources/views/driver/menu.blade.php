<li class="nav-item">
    <a class="nav-link" href="{{ route('driver.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('Logout') }}
    </a>
    <form id="logout-form" action="{{ route('driver.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>