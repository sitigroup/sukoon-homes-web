<nav class="tv-tab-nav mt-2 mb-3" aria-label="Trust score admin">
    @if($tvPermissions['settings'] ?? false)
        <a href="{{ route('trust-verification.trust.badges.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.trust.badges.*') ? 'is-active' : '' }}">
            <i class="bi bi-award"></i> Badges
        </a>
        <a href="{{ route('trust-verification.trust.rules.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.trust.rules.*') ? 'is-active' : '' }}">
            <i class="bi bi-sliders"></i> Trust rules
        </a>
    @endif
    @if($tvPermissions['read'] ?? false)
        <a href="{{ route('trust-verification.trust.customers.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.trust.customers.*') ? 'is-active' : '' }}">
            <i class="bi bi-people"></i> Customer trust
        </a>
    @endif
</nav>
