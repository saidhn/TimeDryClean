<li class="nav-item">
    <a class="nav-link" href="{{ route('employee.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('Logout') }}
    </a>
    <form id="logout-form" action="{{ route('employee.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>