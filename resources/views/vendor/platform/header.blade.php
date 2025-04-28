@push('head')
    <meta name="description" content="Day in Review Admin Dashboard">
    <link href="{{ asset('favicon.ico') }}" sizes="any" type="image/ico" rel="icon">
@endpush

<div class="d-flex align-items-center">
    <div class="dropdown ps-2">
        <div class="nav-link d-flex align-items-center px-2 text-decoration-none" data-bs-toggle="dropdown">
            <img src="{{ asset('images/day-in-review-logo.svg') }}" alt="Day in Review" width="150" height="36" class="me-2">
            <div class="ms-2 pe-2 text-start">
                <div class="fw-medium d-flex align-items-center">
                    <span>Admin Dashboard</span>
                    <x-orchid-icon path="chevron-down" class="ms-1"/>
                </div>
                <small class="text-muted d-block">Manage your content</small>
            </div>
        </div>
        <div class="dropdown-menu dropdown-menu-left dropdown-menu-arrow">
            <a href="{{ route('platform.index') }}" class="dropdown-item">
                <x-orchid-icon path="home" class="me-2"/>
                {{ __('Dashboard') }}
            </a>
            
            <a href="{{ url('/') }}" class="dropdown-item" target="_blank">
                <x-orchid-icon path="globe" class="me-2"/>
                {{ __('View Site') }}
            </a>
            
            <div class="dropdown-divider"></div>
            
            @if(Route::has('platform.systems.users'))
                <a href="{{ route('platform.systems.users') }}" class="dropdown-item">
                    <x-orchid-icon path="user" class="me-2"/>
                    {{ __('Users') }}
                </a>
            @endif
        </div>
    </div>
</div>
