@php $active = $active ?? 'dashboard'; @endphp
<ul class="nav nav-pills mb-3 gap-2 flex-wrap">
    @if(function_exists('has_permissions') && has_permissions('dashboard', 'seo_engine'))
        <li class="nav-item">
            <a class="nav-link {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('seo-engine.dashboard') }}">
                {{ __('seo-engine::seo_engine.nav_dashboard') }}
            </a>
        </li>
    @endif
    @if(function_exists('has_permissions') && has_permissions('settings', 'seo_engine'))
        <li class="nav-item">
            <a class="nav-link {{ $active === 'settings' ? 'active' : '' }}" href="{{ route('seo-engine.settings.index') }}">
                {{ __('seo-engine::seo_engine.nav_settings') }}
            </a>
        </li>
    @endif
    @if(function_exists('has_permissions') && has_permissions('redirects', 'seo_engine'))
        <li class="nav-item">
            <a class="nav-link {{ $active === 'redirects' ? 'active' : '' }}" href="{{ route('seo-engine.redirects.index') }}">
                {{ __('seo-engine::seo_engine.nav_redirects') }}
            </a>
        </li>
    @endif
    @if(function_exists('has_permissions') && has_permissions('templates', 'seo_engine'))
        <li class="nav-item">
            <a class="nav-link {{ $active === 'templates' ? 'active' : '' }}" href="{{ route('seo-engine.templates.index') }}">
                {{ __('seo-engine::seo_engine.nav_templates') }}
            </a>
        </li>
    @endif
    @if(function_exists('has_permissions') && has_permissions('pages', 'seo_engine'))
        <li class="nav-item">
            <a class="nav-link {{ $active === 'pages' ? 'active' : '' }}" href="{{ route('seo-engine.pages.index') }}">
                {{ __('seo-engine::seo_engine.nav_pages') }}
            </a>
        </li>
    @endif
</ul>
