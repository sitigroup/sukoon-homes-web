@if($hasPoliceVerification ?? false)
@php
    $pv = $order->policeVerification;
    $pvStatus = $pv?->status ?? 'not_submitted';
    $statusUrl = $pv?->status_check_url ?: ($rajasthanStatusUrl ?? \App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::RAJASTHAN_STATUS_CHECK_URL);
    $fmtPv = fn ($dt) => $dt
        ? \App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::formatDisplay($dt)
        : null;
    $pvStatusChip = match($pvStatus) {
        'completed' => 'tv-od-chip--success',
        'submitted', 'under_review' => 'tv-od-chip--warning',
        'rejected', 'need_more_documents' => 'tv-od-chip--danger',
        default => 'tv-od-chip--muted',
    };
    $quickActions = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'need_more_documents' => 'Need Docs',
        'completed' => 'Complete',
    ];
    $refDisplay = filled($pv?->reference_number) ? $pv->reference_number : 'Not submitted';
    $mobileDisplay = filled($pv?->applicant_mobile) ? $pv->applicant_mobile : 'Not available';
    $refEmpty = ! filled($pv?->reference_number);
    $mobileEmpty = ! filled($pv?->applicant_mobile);
    $statusRank = [
        'not_submitted' => 0,
        'submitted' => 1,
        'under_review' => 2,
        'need_more_documents' => 2,
        'rejected' => 2,
        'completed' => 3,
    ];
    $currentRank = $statusRank[$pvStatus] ?? 0;
    $showNeedDocsStep = $pvStatus === 'need_more_documents';
    $timelineSteps = [
        ['key' => 'submitted', 'label' => 'Submitted', 'rank' => 1],
        ['key' => 'under_review', 'label' => 'Under Review', 'rank' => 2],
    ];
    if ($showNeedDocsStep) {
        $timelineSteps[] = ['key' => 'need_more_documents', 'label' => 'Need Docs', 'rank' => 2];
    }
    $timelineSteps[] = ['key' => 'completed', 'label' => 'Completed', 'rank' => 3];
    $nowLocal = now()->format('Y-m-d\TH:i');
@endphp
@include('trust-verification::admin.partials.tv-od-accordion-start', [
    'icon' => 'bi-building',
    'title' => 'Police verification',
    'helper' => 'Rajasthan workflow — manual status checks only',
    'meta' => $policeStatuses[$pvStatus] ?? $pvStatus,
])
    @if(!($tvPermissions['update'] ?? false))
        @include('trust-verification::admin.partials.permission-denied')
    @else
        <div class="tv-od-police-workflow" id="police-verification">
            <div class="tv-od-police-workflow__head">
                <div class="tv-od-police-workflow__head-top">
                    <span class="tv-od-chip {{ $pvStatusChip }}">{{ $policeStatuses[$pvStatus] ?? $pvStatus }}</span>
                    <a href="{{ $statusUrl }}" target="_blank" rel="noopener noreferrer" class="btn tv-od-btn-secondary tv-od-police-rajasthan-btn">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        Check Rajasthan status
                    </a>
                </div>
                <dl class="tv-od-police-workflow__head-grid">
                    <div>
                        <dt>Police station</dt>
                        <dd>{{ $pv?->police_station_name ?: '—' }}@if($pv?->city), {{ $pv->city }}@endif</dd>
                    </div>
                    <div>
                        <dt>Reference no.</dt>
                        <dd class="{{ $refEmpty ? 'tv-od-empty-value' : '' }}">{{ $refDisplay }}</dd>
                    </div>
                    <div>
                        <dt>Applicant mobile</dt>
                        <dd class="{{ $mobileEmpty ? 'tv-od-empty-value' : '' }}">{{ $mobileDisplay }}</dd>
                    </div>
                    <div>
                        <dt>Submitted</dt>
                        <dd>{{ $fmtPv($pv?->submitted_at) ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Completed</dt>
                        <dd>{{ $fmtPv($pv?->completed_at) ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="tv-od-police-timeline" aria-label="Police verification progress">
                @foreach($timelineSteps as $index => $step)
                    @php
                        $isDone = $currentRank >= $step['rank'] && $pvStatus !== 'rejected';
                        $isActive = $pvStatus === $step['key']
                            || ($step['key'] === 'under_review' && in_array($pvStatus, ['need_more_documents', 'rejected'], true) && $currentRank >= 2);
                        $isRejected = $pvStatus === 'rejected' && $step['key'] === 'under_review';
                    @endphp
                    <div class="tv-od-police-timeline__step {{ $isDone ? 'is-done' : '' }} {{ $isActive ? 'is-active' : '' }} {{ $isRejected ? 'is-rejected' : '' }}">
                        <span class="tv-od-police-timeline__dot" aria-hidden="true"></span>
                        <span class="tv-od-police-timeline__label">{{ $step['label'] }}</span>
                    </div>
                    @if($index < count($timelineSteps) - 1)
                        <span class="tv-od-police-timeline__connector" aria-hidden="true"></span>
                    @endif
                @endforeach
            </div>

            <div class="tv-od-police-actions">
                <span class="tv-od-police-actions__label">Quick update</span>
                <div class="tv-od-police-actions__buttons">
                    @foreach($quickActions as $quickStatus => $quickLabel)
                        <form method="post" action="{{ route('trust-verification.orders.police.status', $order) }}" class="tv-od-police-quick-form m-0"
                              data-police-quick-status="{{ $quickStatus }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $quickStatus }}">
                            <button type="submit" class="btn tv-od-btn-action {{ $pvStatus === $quickStatus ? 'is-current' : '' }}">{{ $quickLabel }}</button>
                        </form>
                    @endforeach
                    <form method="post" action="{{ route('trust-verification.orders.police.status', $order) }}" class="tv-od-police-quick-form m-0">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn tv-od-btn-secondary tv-od-police-reject-btn">Reject</button>
                    </form>
                </div>
                <p class="tv-od-police-actions__hint">Under Review and Complete stamp today automatically when empty.</p>
            </div>

            <div class="tv-od-police-body">
                <form method="post" action="{{ route('trust-verification.orders.police.update', $order) }}" enctype="multipart/form-data" id="tv-police-verification-form">
                    @csrf

                    <p class="tv-od-section-title">Application details</p>
                    <div class="row tv-od-form-row mb-4">
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Verification type</label>
                            <select name="verification_type" class="form-select tv-od-input">
                                @foreach($policeVerificationTypes as $type)
                                    <option value="{{ $type }}" @selected(($pv?->verification_type ?? $order->order_type) === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Status</label>
                            <select name="status" id="tv-police-status-select" class="form-select tv-od-input">
                                @foreach($policeStatuses as $key => $label)
                                    <option value="{{ $key }}" @selected($pvStatus === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Applicant mobile</label>
                            <input type="text" name="applicant_mobile" class="form-control tv-od-input" value="{{ old('applicant_mobile', $pv?->applicant_mobile) }}" maxlength="20" placeholder="Not available">
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Reference number</label>
                            <input type="text" name="reference_number" class="form-control tv-od-input" value="{{ old('reference_number', $pv?->reference_number) }}" maxlength="64" placeholder="Not submitted">
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">Police station</label>
                            <input type="text" name="police_station_name" class="form-control tv-od-input" value="{{ old('police_station_name', $pv?->police_station_name) }}" maxlength="160">
                        </div>
                        <div class="col-md-4 tv-od-field-group">
                            <label class="tv-od-label">City</label>
                            <input type="text" name="city" class="form-control tv-od-input" value="{{ old('city', $pv?->city ?? $cityLabel) }}" maxlength="80">
                        </div>
                        <div class="col-md-6 tv-od-field-group">
                            <label class="tv-od-label">District</label>
                            <input type="text" name="district" class="form-control tv-od-input" value="{{ old('district', $pv?->district ?? $cityLabel) }}" maxlength="80">
                        </div>
                    </div>

                    <details class="tv-od-police-advanced mb-4">
                        <summary class="tv-od-police-advanced__summary">Advanced options</summary>
                        <div class="tv-od-police-advanced__body row tv-od-form-row">
                            <div class="col-md-6 tv-od-field-group">
                                <label class="tv-od-label">Police Provider Reference</label>
                                <input type="text" name="provider_reference_number" class="form-control tv-od-input" value="{{ old('provider_reference_number', $pv?->provider_reference_number) }}" maxlength="64" placeholder="Optional — integrations only">
                            </div>
                            <div class="col-md-6 tv-od-field-group">
                                <label class="tv-od-label">Status check URL</label>
                                <input type="url" name="status_check_url" class="form-control tv-od-input" value="{{ old('status_check_url', $pv?->status_check_url) }}" placeholder="{{ $rajasthanStatusUrl ?? '' }}">
                                <p class="tv-od-field-hint mb-0">Leave blank to use the default Rajasthan citizen portal link above.</p>
                            </div>
                        </div>
                    </details>

                    <p class="tv-od-section-title">Timeline dates</p>
                    <div class="row tv-od-form-row mb-4">
                        <div class="col-md-3 tv-od-field-group">
                            <label class="tv-od-label">Submitted at</label>
                            <input type="datetime-local" name="submitted_at" class="form-control tv-od-input"
                                   value="{{ $pv?->submitted_at ? $pv->submitted_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="col-md-3 tv-od-field-group">
                            <label class="tv-od-label">Reviewed at</label>
                            <input type="datetime-local" name="reviewed_at" id="tv-police-reviewed-at" class="form-control tv-od-input"
                                   value="{{ $pv?->reviewed_at ? $pv->reviewed_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="col-md-3 tv-od-field-group">
                            <label class="tv-od-label">Completed at</label>
                            <input type="datetime-local" name="completed_at" id="tv-police-completed-at" class="form-control tv-od-input"
                                   value="{{ $pv?->completed_at ? $pv->completed_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="col-md-3 tv-od-field-group">
                            <label class="tv-od-label">Rejected at</label>
                            <input type="datetime-local" name="rejected_at" class="form-control tv-od-input"
                                   value="{{ $pv?->rejected_at ? $pv->rejected_at->format('Y-m-d\TH:i') : '' }}">
                        </div>
                    </div>

                    <p class="tv-od-section-title">Notes &amp; outcome</p>
                    <div class="row tv-od-form-row mb-4">
                        <div class="col-12 tv-od-field-group">
                            <label class="tv-od-label">Admin notes</label>
                            <textarea name="admin_notes" class="form-control tv-od-input" rows="3">{{ old('admin_notes', $pv?->admin_notes) }}</textarea>
                        </div>
                        <div class="col-12 tv-od-field-group">
                            <label class="tv-od-label">Officer notes</label>
                            <textarea name="officer_notes" class="form-control tv-od-input" rows="3">{{ old('officer_notes', $pv?->officer_notes) }}</textarea>
                        </div>
                        <div class="col-12 tv-od-field-group">
                            <label class="tv-od-label">Rejection reason</label>
                            <textarea name="rejection_reason" class="form-control tv-od-input" rows="3">{{ old('rejection_reason', $pv?->rejection_reason) }}</textarea>
                        </div>
                    </div>

                    <p class="tv-od-section-title">Documents</p>
                    <div class="row tv-od-form-row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="tv-od-police-upload-card">
                                <div class="tv-od-police-upload-card__icon" aria-hidden="true">
                                    <i class="bi bi-file-earmark-check"></i>
                                </div>
                                <div class="tv-od-police-upload-card__body">
                                    <h3 class="tv-od-police-upload-card__title">Acknowledgement</h3>
                                    <p class="tv-od-police-upload-card__meta">PDF, JPG or PNG · max 5 MB</p>
                                    @if($pv?->acknowledgement_document_path)
                                        <a href="{{ route('trust-verification.orders.police.acknowledgement.download', $order) }}" class="tv-od-police-upload-card__link">
                                            <i class="bi bi-download" aria-hidden="true"></i> Download current file
                                        </a>
                                    @else
                                        <span class="tv-od-police-upload-card__empty">No file uploaded yet</span>
                                    @endif
                                    <label class="tv-od-police-upload-card__file">
                                        <span class="btn tv-od-btn-secondary btn-sm">{{ $pv?->acknowledgement_document_path ? 'Replace file' : 'Upload acknowledgement' }}</span>
                                        <input type="file" name="acknowledgement" class="tv-od-police-upload-card__input" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="tv-od-police-upload-card">
                                <div class="tv-od-police-upload-card__icon tv-od-police-upload-card__icon--cert" aria-hidden="true">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="tv-od-police-upload-card__body">
                                    <h3 class="tv-od-police-upload-card__title">Certificate / report</h3>
                                    <p class="tv-od-police-upload-card__meta">PDF, JPG or PNG · max 10 MB</p>
                                    @if($pv?->certificate_document_path)
                                        <a href="{{ route('trust-verification.orders.police.certificate.download', $order) }}" class="tv-od-police-upload-card__link">
                                            <i class="bi bi-download" aria-hidden="true"></i> Download current file
                                        </a>
                                    @else
                                        <span class="tv-od-police-upload-card__empty">No file uploaded yet</span>
                                    @endif
                                    <label class="tv-od-police-upload-card__file">
                                        <span class="btn tv-od-btn-secondary btn-sm">{{ $pv?->certificate_document_path ? 'Replace file' : 'Upload certificate' }}</span>
                                        <input type="file" name="certificate" class="tv-od-police-upload-card__input" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tv-od-police-save-row">
                        <button type="submit" class="btn tv-od-btn-save">Save police verification</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        (function () {
            var statusSelect = document.getElementById('tv-police-status-select');
            var reviewedAt = document.getElementById('tv-police-reviewed-at');
            var completedAt = document.getElementById('tv-police-completed-at');
            var nowLocal = @json($nowLocal);

            function stampIfEmpty(input) {
                if (input && !input.value) {
                    input.value = nowLocal;
                }
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', function () {
                    if (this.value === 'under_review') {
                        stampIfEmpty(reviewedAt);
                    }
                    if (this.value === 'completed') {
                        stampIfEmpty(completedAt);
                    }
                });
            }
        })();
        </script>
    @endif
@include('trust-verification::admin.partials.tv-od-accordion-end')
@endif
