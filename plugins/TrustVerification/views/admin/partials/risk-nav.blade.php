<nav class="tv-sub-nav mb-3" aria-label="Risk Center">
    <a href="{{ route('trust-verification.risk.profiles.index') }}"
       class="tv-sub-nav__link {{ request()->routeIs('trust-verification.risk.profiles.*') ? 'is-active' : '' }}">Risk profiles</a>
    <a href="{{ route('trust-verification.risk.signals.index') }}"
       class="tv-sub-nav__link {{ request()->routeIs('trust-verification.risk.signals.*') ? 'is-active' : '' }}">Risk signals</a>
</nav>
