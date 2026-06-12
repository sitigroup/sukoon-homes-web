@if($hasReferenceCheck ?? false)
@php
    $fmtRef = fn ($dt) => $dt
        ? \App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::formatDisplay($dt)
        : '—';
    $refStatusChip = fn ($status) => match($status) {
        'verified', 'completed' => 'tv-od-chip--success',
        'contacted', 'in_progress' => 'tv-od-chip--warning',
        'failed', 'unreachable', 'declined' => 'tv-od-chip--danger',
        default => 'tv-od-chip--muted',
    };
@endphp
@include('trust-verification::admin.partials.tv-od-accordion-start', [
    'icon' => 'bi-telephone-outbound',
    'title' => 'Reference check',
    'helper' => 'Landlord and employer references',
    'meta' => $referenceProgress['summary'] ?? '',
])
    @if(!($tvPermissions['update'] ?? false))
        @include('trust-verification::admin.partials.permission-denied')
    @else
        @forelse($order->referenceContacts as $ref)
            <div class="tv-od-ref-card">
                <div class="tv-od-ref-card__head">
                    <div>
                        <p class="tv-od-ref-card__type">{{ $referenceTypes[$ref->reference_type] ?? $ref->reference_type }}</p>
                        <p class="tv-od-ref-card__mobile">
                            {{ $ref->mobile }}
                            @if($ref->email)
                                · {{ $ref->email }}
                            @endif
                        </p>
                        @if($ref->name)
                            <p class="mb-0 text-muted">
                                {{ $ref->name }}
                                @if($ref->relation)
                                    ({{ $ref->relation }})
                                @endif
                            </p>
                        @endif
                    </div>
                    <span class="tv-od-chip {{ $refStatusChip($ref->status) }}">{{ $referenceStatuses[$ref->status] ?? $ref->status }}</span>
                </div>

                @if($ref->notes)
                    <p class="mb-3"><span class="tv-od-label d-block">Customer notes</span>{{ $ref->notes }}</p>
                @endif

                <dl class="tv-od-ref-kv mb-3">
                    <div><dt>Contacted</dt><dd>{{ $fmtRef($ref->contacted_at) }}</dd></div>
                    <div><dt>Verified</dt><dd>{{ $fmtRef($ref->verified_at) }}</dd></div>
                    <div><dt>Last update</dt><dd>{{ $fmtRef($ref->last_status_at) }}</dd></div>
                    @if($ref->call_outcome)
                        <div class="col-12"><dt>Call outcome</dt><dd>{{ $ref->call_outcome }}</dd></div>
                    @endif
                </dl>

                <form method="post" action="{{ route('trust-verification.orders.references.update', [$order, $ref]) }}">
                    @csrf
                    <div class="row tv-od-form-row">
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Status</label>
                            <select name="status" class="form-select tv-od-input">
                                @foreach($referenceStatuses as $key => $label)
                                    <option value="{{ $key }}" @selected($ref->status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Contacted at</label>
                            <input type="datetime-local" name="contacted_at" class="form-control tv-od-input"
                                   value="{{ $ref->contacted_at ? $ref->contacted_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Verified at</label>
                            <input type="datetime-local" name="verified_at" class="form-control tv-od-input"
                                   value="{{ $ref->verified_at ? $ref->verified_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="col-md-6 tv-od-field-group">
                            <label class="tv-od-label">Call outcome</label>
                            <input type="text" name="call_outcome" class="form-control tv-od-input"
                                   value="{{ $ref->call_outcome }}" placeholder="Spoke with reference, confirmed tenancy…">
                        </div>
                        <div class="col-md-6 tv-od-field-group">
                            <label class="tv-od-label">External ref ID</label>
                            <input type="text" name="external_ref_id" class="form-control tv-od-input"
                                   value="{{ $ref->external_ref_id }}" placeholder="Future API ID">
                        </div>
                        <div class="col-12 tv-od-field-group">
                            <label class="tv-od-label">Internal admin notes</label>
                            <textarea name="admin_notes" class="form-control tv-od-input" rows="3">{{ $ref->admin_notes }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn tv-od-btn-save">Save reference</button>
                        </div>
                    </div>
                </form>
            </div>
        @empty
            <p class="text-muted mb-4">No reference contacts submitted yet.</p>
        @endforelse

        <h3 class="tv-od-section-title">Add reference contact</h3>
        <form method="post" action="{{ route('trust-verification.orders.references.store', $order) }}">
            @csrf
            <div class="row tv-od-form-row">
                <div class="col-md-4 tv-od-field-group">
                    <label class="tv-od-label">Type</label>
                    <select name="reference_type" class="form-select tv-od-input" required>
                        @foreach($referenceTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 tv-od-field-group">
                    <label class="tv-od-label">Name</label>
                    <input type="text" name="name" class="form-control tv-od-input" required maxlength="120">
                </div>
                <div class="col-md-4 tv-od-field-group">
                    <label class="tv-od-label">Relation</label>
                    <input type="text" name="relation" class="form-control tv-od-input" maxlength="80" placeholder="Landlord, Manager…">
                </div>
                <div class="col-md-4 tv-od-field-group">
                    <label class="tv-od-label">Mobile</label>
                    <input type="text" name="mobile" class="form-control tv-od-input" required maxlength="20">
                </div>
                <div class="col-md-4 tv-od-field-group">
                    <label class="tv-od-label">Email</label>
                    <input type="email" name="email" class="form-control tv-od-input" maxlength="190">
                </div>
                <div class="col-md-4 tv-od-field-group">
                    <label class="tv-od-label">Customer notes</label>
                    <input type="text" name="notes" class="form-control tv-od-input" maxlength="2000">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn tv-od-btn-secondary">Add reference</button>
                </div>
            </div>
        </form>
    @endif
@include('trust-verification::admin.partials.tv-od-accordion-end')
@endif
