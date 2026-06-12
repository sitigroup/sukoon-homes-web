@php
    use App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService;
    $riskProfile = $riskProfile ?? null;
    $riskSignals = $riskSignals ?? collect();
@endphp
@if($order->customer_id)
    @include('trust-verification::admin.partials.tv-od-accordion-start', [
        'icon' => 'bi-shield-exclamation',
        'title' => 'Fraud risk (admin only)',
        'helper' => 'Internal Sukoon risk engine — never shown to customers or on Homes',
        'meta' => $riskProfile ? ucfirst($riskProfile->risk_level).' · '.$riskProfile->risk_score.'/100' : 'Not calculated',
    ])
        @if(!$riskProfile && $riskSignals->isEmpty())
            <p class="text-muted mb-0">No risk profile yet.
                @if($tvPermissions['update'] ?? false)
                    <form method="post" action="{{ route('trust-verification.risk.profiles.refresh', $order->customer_id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Refresh risk</button>
                    </form>
                @endif
            </p>
        @else
            <dl class="tv-od-dl mb-3">
                <div class="tv-od-dl__row">
                    <dt>Risk score</dt>
                    <dd>
                        <strong>{{ $riskProfile?->risk_score ?? 0 }}</strong> / 100
                        @if($riskProfile)
                            <span class="badge bg-{{ TrustVerificationFraudRiskService::levelBadgeClass($riskProfile->risk_level) }} ms-1">
                                {{ ucfirst($riskProfile->risk_level) }}
                            </span>
                        @endif
                    </dd>
                </div>
                @if($riskProfile && $riskProfile->manual_override != 0)
                    <div class="tv-od-dl__row">
                        <dt>Manual override</dt>
                        <dd>{{ $riskProfile->manual_override >= 0 ? '+' : '' }}{{ $riskProfile->manual_override }}</dd>
                    </div>
                @endif
                @if($riskProfile?->notes)
                    <div class="tv-od-dl__row">
                        <dt>Manual notes</dt>
                        <dd>{{ $riskProfile->notes }}</dd>
                    </div>
                @endif
            </dl>

            @if($riskSignals->isNotEmpty())
                <h3 class="h6 text-muted text-uppercase small mb-2">Signals on this order / customer</h3>
                <ul class="list-unstyled mb-3">
                    @foreach($riskSignals as $signal)
                        <li class="mb-2 pb-2 border-bottom">
                            <strong>{{ TrustVerificationFraudRiskService::signalLabel($signal->signal_type) }}</strong>
                            <span class="text-muted">+{{ $signal->risk_points }}</span>
                            <span class="badge bg-light text-dark border ms-1">{{ $signal->source }}</span>
                            @if($signal->notes)
                                <div class="small text-muted">{{ $signal->notes }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('trust-verification.risk.profiles.show', $order->customer_id) }}" class="btn btn-sm tv-od-btn-secondary">
                Open risk center
            </a>
        @endif
    @include('trust-verification::admin.partials.tv-od-accordion-end')
@endif
