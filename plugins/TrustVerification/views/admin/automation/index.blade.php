@extends('layouts.main')

@section('title')
    Trust Verification Automation
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <header class="tv-admin-header">
            <div>
                <h1 class="tv-admin-header__title">Automation</h1>
                <p class="tv-admin-header__meta mb-0">Documents, check automation, order numbering, and PII retention.</p>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{!! nl2br(e(session('success'))) !!}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <form method="post" action="{{ route('trust-verification.automation.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="tv-admin-card mb-3">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">Customer documents</strong>
                            <span class="tv-admin-header__meta">Web wizard upload settings</span>
                        </div>
                        <div class="tv-admin-card__body">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="documents_enabled" name="documents_enabled" value="1" @checked($settings['documents_enabled'] ?? false)>
                                <label class="form-check-label" for="documents_enabled">Enable document upload on web</label>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="documents_required" name="documents_required" value="1" @checked($settings['documents_required'] ?? false)>
                                <label class="form-check-label" for="documents_required">Require documents before submit</label>
                            </div>
                            <label class="form-label small text-muted">Required document types</label>
                            <div class="row g-2">
                                @foreach($documentTypes as $key => $label)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="required_document_types[]" value="{{ $key }}" id="doc_{{ $key }}"
                                                @checked(in_array($key, (array) ($settings['required_document_types'] ?? []), true))>
                                            <label class="form-check-label" for="doc_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="tv-admin-card mb-3">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">Check automation</strong>
                        </div>
                        <div class="tv-admin-card__body">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="automation_enabled" name="automation_enabled" value="1" @checked($settings['automation_enabled'] ?? false)>
                                <label class="form-check-label" for="automation_enabled">Enable automation engine</label>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Provider</label>
                                    <select name="automation_provider" class="form-select form-select-sm">
                                        @foreach($providers as $key => $label)
                                            <option value="{{ $key }}" @selected(($settings['automation_provider'] ?? 'rules') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="automation_on_paid" name="automation_on_paid" value="1" @checked($settings['automation_on_paid'] ?? true)>
                                <label class="form-check-label" for="automation_on_paid">Auto-run when payment is paid or waived</label>
                            </div>
                            <div class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" id="automation_auto_apply" name="automation_auto_apply" value="1" @checked($settings['automation_auto_apply'] ?? true)>
                                <label class="form-check-label" for="automation_auto_apply">Apply provider results to check items automatically</label>
                            </div>
                        </div>
                    </div>

                    <div class="tv-admin-card mb-3">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">Order number series</strong>
                        </div>
                        <div class="tv-admin-card__body">
                            <div class="tv-help-block mb-3">
                                Applies to <strong>new orders only</strong>. Existing order numbers are never changed.
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted" for="order_number_prefix">Order prefix</label>
                                    <input type="text" id="order_number_prefix" name="order_number_prefix" class="form-control form-control-sm text-uppercase"
                                           maxlength="8" pattern="[A-Za-z0-9]+"
                                           value="{{ old('order_number_prefix', $settings['order_number_prefix'] ?? 'TV') }}">
                                    <span class="small text-muted">Uppercase letters/numbers, max 8</span>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small text-muted" for="order_number_format">Number format</label>
                                    <select id="order_number_format" name="order_number_format" class="form-select form-select-sm">
                                        @foreach(($orderNumberMeta['order_number_formats'] ?? []) as $key => $label)
                                            <option value="{{ $key }}" @selected(old('order_number_format', $settings['order_number_format'] ?? 'random') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted" for="order_number_next_sequence">Next sequence number</label>
                                    <input type="number" id="order_number_next_sequence" name="order_number_next_sequence" class="form-control form-control-sm" min="1"
                                           value="{{ old('order_number_next_sequence', $settings['order_number_next_sequence'] ?? 1) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted" for="order_number_digits">Minimum digit length</label>
                                    <input type="number" id="order_number_digits" name="order_number_digits" class="form-control form-control-sm" min="4" max="12"
                                           value="{{ old('order_number_digits', $settings['order_number_digits'] ?? 6) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted" for="order_number_separator">Separator</label>
                                    <input type="text" id="order_number_separator" name="order_number_separator" class="form-control form-control-sm" maxlength="1"
                                           value="{{ old('order_number_separator', $settings['order_number_separator'] ?? '-') }}">
                                </div>
                            </div>
                            <div class="alert alert-light border mb-0 py-2">
                                <span class="small text-muted d-block mb-1">Preview (Barmer sample city)</span>
                                <code id="order_number_preview" class="fs-6">{{ $orderNumberMeta['order_number_preview'] ?? 'TV-XXXXXXXX' }}</code>
                            </div>
                        </div>
                    </div>

                    <div class="tv-admin-card mb-3">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">PII retention</strong>
                            <span class="tv-admin-header__meta">Documents &amp; reports</span>
                        </div>
                        <div class="tv-admin-card__body">
                            <div class="tv-help-block">
                                Scheduled cleanup: <code>php artisan trust-verification:cleanup-pii</code> (add <code>--dry-run</code> to preview).
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Document retention (days)</label>
                                    <input type="number" name="document_retention_days" class="form-control form-control-sm" min="1" max="3650"
                                           value="{{ old('document_retention_days', $settings['document_retention_days'] ?? 90) }}">
                                    <span class="small text-muted">Completed orders — delete files only</span>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Report retention (days)</label>
                                    <input type="number" name="report_retention_days" class="form-control form-control-sm" min="1" max="3650"
                                           value="{{ old('report_retention_days', $settings['report_retention_days'] ?? 365) }}">
                                    <span class="small text-muted">Completed orders — delete report PDF</span>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Cancelled unpaid (days)</label>
                                    <input type="number" name="delete_cancelled_unpaid_after_days" class="form-control form-control-sm" min="1" max="365"
                                           value="{{ old('delete_cancelled_unpaid_after_days', $settings['delete_cancelled_unpaid_after_days'] ?? 7) }}">
                                    <span class="small text-muted">Cancel + pending/failed payment — purge all PII</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tv-admin-card mb-3">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">Webhook &amp; HTTP API</strong>
                            <span class="tv-admin-header__meta">IDfy / AuthBridge style</span>
                        </div>
                        <div class="tv-admin-card__body">
                            <div class="tv-help-block mb-3">
                                POST <code>{{ url('/api/trust-verification/webhook/automation') }}</code> with header <code>X-TV-Webhook-Secret</code> for async results.
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted">API base URL</label>
                                <input type="url" name="http_api_base_url" class="form-control form-control-sm" placeholder="https://api.vendor.com/v1" value="{{ old('http_api_base_url', $settings['http_api_base_url'] ?? '') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted">API key</label>
                                <input type="text" name="http_api_key" class="form-control form-control-sm" value="{{ old('http_api_key', $settings['http_api_key'] ?? '') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted">API secret (optional)</label>
                                <input type="text" name="http_api_secret" class="form-control form-control-sm" value="{{ old('http_api_secret', $settings['http_api_secret'] ?? '') }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small text-muted">Webhook secret</label>
                                <input type="text" name="webhook_secret" class="form-control form-control-sm" placeholder="Set a strong random string" value="{{ old('webhook_secret', $settings['webhook_secret'] ?? '') }}">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn tv-admin-btn-primary">Save settings</button>
                </form>
            </div>

            <div class="col-lg-5 tv-admin-sidebar">
                    <div class="tv-admin-card mb-3">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">Payment reconciliation</strong>
                        </div>
                        <div class="tv-admin-card__body">
                            <p class="small text-muted mb-2">Sync tv_orders payment_status from linked Cashfree transactions.</p>
                            <form method="post" action="{{ route('trust-verification.automation.reconcile') }}" class="d-flex flex-wrap gap-2">
                                @csrf
                                <button type="submit" class="btn btn-sm tv-admin-btn-primary">Run reconcile</button>
                                <button type="submit" name="dry_run" value="1" class="btn btn-sm tv-admin-btn-secondary">Dry run</button>
                            </form>
                        </div>
                    </div>

                    <div class="tv-admin-card">
                        <div class="tv-admin-card__header">
                            <strong class="tv-page-header__title">Status</strong>
                            @if($automationMeta['http_api_configured'] ?? false)
                                <span class="tv-admin-chip-success">HTTP API configured</span>
                            @else
                                <span class="tv-admin-chip-muted">Rules / manual mode</span>
                            @endif
                        </div>
                        <div class="tv-admin-card__body small">
                            <p class="mb-1">Automation: {{ ($settings['automation_enabled'] ?? false) ? 'On' : 'Off' }}</p>
                            <p class="mb-1">Provider: {{ $providers[$settings['automation_provider'] ?? 'rules'] ?? '—' }}</p>
                            <p class="mb-1">Documents: {{ ($settings['documents_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</p>
                            <p class="mb-0">Retention: docs {{ $settings['document_retention_days'] ?? 90 }}d · reports {{ $settings['report_retention_days'] ?? 365 }}d · cancelled {{ $settings['delete_cancelled_unpaid_after_days'] ?? 7 }}d</p>
                        </div>
                    </div>
            </div>
        </div>

        <div class="tv-admin-card mt-3">
            <div class="tv-admin-card__header">
                <strong class="tv-page-header__title">Recent automation runs</strong>
            </div>
            <div class="tv-admin-card__body tv-admin-card__body--flush">
                <div class="table-responsive">
                    <table class="table table-sm table-striped tv-admin-table mb-0">
                        <thead>
                        <tr>
                            <th>When</th>
                            <th>Order</th>
                            <th>Provider</th>
                            <th>Trigger</th>
                            <th>Status</th>
                            <th>Checks</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentRuns as $run)
                            <tr>
                                <td class="small">{{ $run->created_at?->format('d M H:i') }}</td>
                                <td>
                                    @if($run->order)
                                        <a href="{{ route('trust-verification.orders.show', $run->order) }}">{{ $run->order->order_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $run->provider }}</td>
                                <td>{{ $run->trigger }}</td>
                                <td>
                                    @if($run->status === 'completed')
                                        <span class="tv-admin-chip-success">completed</span>
                                    @elseif($run->status === 'failed')
                                        <span class="tv-admin-chip-danger" title="{{ $run->error_message }}">failed</span>
                                    @else
                                        <span class="tv-admin-chip-muted">{{ $run->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $run->checks_updated }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No automation runs yet</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const previewCity = @json($previewCitySlug ?? 'barmer');
            const fields = ['order_number_prefix', 'order_number_format', 'order_number_next_sequence', 'order_number_digits', 'order_number_separator'];
            const previewEl = document.getElementById('order_number_preview');

            function cityCode(slug) {
                const clean = String(slug || '').toLowerCase().replace(/[^a-z]/g, '');
                if (!clean) return 'GEN';
                const consonants = clean.replace(/[aeiou]/g, '');
                const base = (consonants || clean).substring(0, 3).toUpperCase();
                return base.padEnd(3, 'X');
            }

            function randomPart() {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                let out = '';
                for (let i = 0; i < 8; i++) out += chars.charAt(Math.floor(Math.random() * chars.length));
                return out;
            }

            function updatePreview() {
                if (!previewEl) return;
                const prefix = (document.getElementById('order_number_prefix')?.value || 'TV').toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8) || 'TV';
                const format = document.getElementById('order_number_format')?.value || 'random';
                const seq = Math.max(1, parseInt(document.getElementById('order_number_next_sequence')?.value || '1', 10));
                const digits = Math.min(12, Math.max(4, parseInt(document.getElementById('order_number_digits')?.value || '6', 10)));
                const sep = (document.getElementById('order_number_separator')?.value || '-').substring(0, 1);
                const padded = String(seq).padStart(digits, '0');
                let preview = prefix + sep;

                if (format === 'sequential') {
                    preview = prefix + sep + padded;
                } else if (format === 'year_sequence') {
                    preview = prefix + sep + new Date().getFullYear() + sep + padded;
                } else if (format === 'city_sequence') {
                    preview = prefix + sep + cityCode(previewCity) + sep + padded;
                } else {
                    preview = prefix + sep + randomPart();
                }

                previewEl.textContent = preview;
            }

            fields.forEach(function (id) {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updatePreview);
                    el.addEventListener('change', updatePreview);
                }
            });
            updatePreview();
        })();
    </script>
@endsection
