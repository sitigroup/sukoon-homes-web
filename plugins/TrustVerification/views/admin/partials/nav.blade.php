<nav class="tv-tab-nav" aria-label="Trust Verification admin">
    @if($tvPermissions['read'] ?? false)
        <a href="{{ route('trust-verification.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.index', 'trust-verification.orders.*') ? 'is-active' : '' }}">
            <i class="bi bi-inbox" aria-hidden="true"></i>
            Orders
        </a>
    @endif
    @if($tvPermissions['settings'] ?? false)
        <a href="{{ route('trust-verification.packages.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.packages.*') ? 'is-active' : '' }}">
            <i class="bi bi-box-seam" aria-hidden="true"></i>
            Packages
        </a>
        <a href="{{ route('trust-verification.cities.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.cities.*') ? 'is-active' : '' }}">
            <i class="bi bi-geo-alt" aria-hidden="true"></i>
            Cities
        </a>
        <a href="{{ route('trust-verification.automation.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.automation.*') ? 'is-active' : '' }}">
            <i class="bi bi-gear-wide-connected" aria-hidden="true"></i>
            Automation
        </a>
        <a href="{{ route('trust-verification.content.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.content.*') ? 'is-active' : '' }}">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            Content
        </a>
        <a href="{{ route('trust-verification.sample-reports.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.sample-reports.*') ? 'is-active' : '' }}">
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
            Sample Reports
        </a>
        <a href="{{ route('trust-verification.verification-badges.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.verification-badges.*') ? 'is-active' : '' }}">
            <i class="bi bi-patch-check" aria-hidden="true"></i>
            Badges
        </a>
        <a href="{{ route('trust-verification.trust.badges.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.trust.*') ? 'is-active' : '' }}">
            <i class="bi bi-award" aria-hidden="true"></i>
            Trust Score
        </a>
        <a href="{{ route('trust-verification.risk.profiles.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.risk.*') ? 'is-active' : '' }}">
            <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
            Risk Center
        </a>
        <a href="{{ route('trust-verification.reliability.index') }}"
           class="tv-tab {{ request()->routeIs('trust-verification.reliability.*') ? 'is-active' : '' }}">
            <i class="bi bi-person-check" aria-hidden="true"></i>
            Tenant Reliability
        </a>
    @endif
</nav>
