<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.users.index') }}">
        {{ __('messages.manage_users') }}
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        {{ __('messages.logout') }}
    </a>
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>