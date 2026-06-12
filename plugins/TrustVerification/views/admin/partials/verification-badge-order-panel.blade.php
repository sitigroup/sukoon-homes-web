@php

    $badge = $verificationBadge ?? null;

    $badgeBlockers = $verificationBadgeBlockers ?? [];

    $isOwnerOrder = ($order->order_type ?? '') === 'owner';

@endphp

@include('trust-verification::admin.partials.tv-od-accordion-start', [

    'id' => 'tvOdVerificationBadge',

    'title' => 'Verification badge',

    'meta' => $badge ? $badge->badge_number : 'Not issued',

    'open' => true,

])

    @if($badge)

        <dl class="row small mb-3">

            <dt class="col-sm-4">Number</dt><dd class="col-sm-8"><code>{{ $badge->badge_number }}</code></dd>

            <dt class="col-sm-4">Title</dt><dd class="col-sm-8">{{ $badge->displayTitle() }}</dd>

            <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ ucfirst($badge->status) }}</dd>

            <dt class="col-sm-4">Issued</dt><dd class="col-sm-8">{{ $badge->issued_at?->format('d M Y H:i') ?? '—' }}</dd>

            <dt class="col-sm-4">Expires</dt><dd class="col-sm-8">{{ $badge->expires_at?->format('d M Y H:i') ?? '—' }}</dd>

        </dl>

        <div class="d-flex flex-wrap gap-2">

            <a href="{{ route('trust-verification.verification-badges.show', $badge) }}" class="btn btn-sm tv-admin-btn-primary">Manage badge</a>

            <a href="{{ route('trust-verification.verification-badges.download', $badge) }}" class="btn btn-sm tv-admin-btn-secondary" target="_blank" rel="noopener">Download certificate</a>

        </div>

    @else

        @if(count($badgeBlockers) > 0)

            <p class="small text-muted mb-2">Not eligible yet:</p>

            <ul class="small mb-3">

                @foreach($badgeBlockers as $line)

                    <li>{{ $line }}</li>

                @endforeach

            </ul>

        @else

            <p class="text-success small mb-3">Order meets badge issuance requirements.</p>

        @endif

        @if($tvPermissions['update'] ?? false)

            @if($isOwnerOrder)

                <div class="d-flex flex-wrap gap-2 mb-2">

                    <form method="post" action="{{ route('trust-verification.verification-badges.issue-owner', $order) }}">

                        @csrf

                        <button type="submit" class="btn btn-sm tv-admin-btn-primary">Issue Owner Badge</button>

                    </form>

                    <form method="post" action="{{ route('trust-verification.verification-badges.issue-verified-owner', $order) }}"

                          onsubmit="return confirm('Issue verified SVO for this owner order (admin override)?');">

                        @csrf

                        <button type="submit" class="btn btn-sm tv-admin-btn-secondary">Issue Verified Owner Badge (Admin Override)</button>

                    </form>

                </div>

            @else

                <form method="post" action="{{ route('trust-verification.verification-badges.issue', $order) }}" class="d-flex flex-wrap gap-2 align-items-center">

                    @csrf

                    <button type="submit" class="btn btn-sm tv-admin-btn-primary">Issue badge now</button>

                    <label class="small mb-0">

                        <input type="checkbox" name="force_verified" value="1"> Issue as verified (admin override)

                    </label>

                    <label class="small mb-0">

                        <input type="checkbox" name="force_pending" value="1"> Force pending only

                    </label>

                </form>

            @endif

        @endif

    @endif

@include('trust-verification::admin.partials.tv-od-accordion-end')

