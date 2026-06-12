@extends('layouts.main')

@section('title')
    Theme Settings
@endsection

@section('content')
    <section class="section st-admin" id="sukoon-theme-admin">
        @include('theme::admin.partials.theme-admin-styles')

        <div class="st-page-header">
            <div>
                <h1 class="st-page-header__title">Appearance → Theme Settings</h1>
                <p class="st-page-header__meta mb-0">
                    Sukoon premium luxury theme — live preview, draft, publish, and version history.
                    @if(empty($is_published))
                        <span class="st-badge st-badge--draft ms-2">Not published — live site unchanged</span>
                    @elseif($has_unpublished_draft)
                        <span class="st-badge st-badge--draft ms-2">Unpublished draft</span>
                    @else
                        <span class="st-badge ms-2">Published</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="row g-4 st-layout">
            <div class="col-lg-7">
                <div class="st-card">
                    <div class="st-card__header">Theme presets</div>
                    <div class="st-card__body">
                        <div class="row g-3" id="st-presets">
                            @foreach($presets as $preset)
                                <div class="col-md-4">
                                    <div class="st-preset {{ ($active_preset ?? '') === $preset['slug'] ? 'is-active' : '' }}"
                                         data-preset="{{ $preset['slug'] }}">
                                        <strong>{{ $preset['name'] }}</strong>
                                        <p class="text-muted small mb-0">{{ $preset['description'] }}</p>
                                        <div class="st-preset__swatches">
                                            @foreach(['primary', 'accent', 'section'] as $swatch)
                                                <span class="st-preset__swatch"
                                                      style="background: {{ $preset['tokens'][$swatch] }}"></span>
                                            @endforeach
                                        </div>
                                        <button type="button" class="st-btn st-btn--ghost st-btn-apply-preset w-100"
                                                data-preset="{{ $preset['slug'] }}">Apply to draft</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="st-card">
                    <div class="st-card__header">Color &amp; surface tokens</div>
                    <div class="st-card__body">
                        <form id="st-theme-form">
                            <div class="st-grid">
                                @php
                                    $tokenLabels = [
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'accent' => 'Accent',
                                        'sidebar' => 'Sidebar',
                                        'card' => 'Card',
                                        'border' => 'Border',
                                        'button' => 'Button',
                                        'hover' => 'Hover',
                                        'link' => 'Link',
                                        'muted' => 'Muted',
                                        'white' => 'White',
                                        'section' => 'Section',
                                    ];
                                @endphp
                                @foreach($tokenLabels as $key => $label)
                                    <div class="st-field">
                                        <label for="token_{{ $key }}">{{ $label }}</label>
                                        <input type="color" id="token_{{ $key }}" name="tokens[{{ $key }}]"
                                               value="{{ $draft['tokens'][$key] ?? '#1F2937' }}"
                                               data-token="{{ $key }}">
                                        <input type="text" class="mt-1 token-hex" data-for="{{ $key }}"
                                               value="{{ $draft['tokens'][$key] ?? '#1F2937' }}">
                                    </div>
                                @endforeach
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6 st-field">
                                    <label for="typography">Typography</label>
                                    <select id="typography" name="typography">
                                        @foreach([
                                            'Manrope, system-ui, -apple-system, sans-serif' => 'Manrope (Sukoon default)',
                                            'Inter, system-ui, -apple-system, sans-serif' => 'Inter',
                                            'SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif' => 'SF Pro (Apple)',
                                            'Georgia, Times New Roman, serif' => 'Georgia Serif',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" @selected(($draft['typography'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 st-field">
                                    <label for="border_radius">Border radius</label>
                                    <select id="border_radius" name="border_radius">
                                        @foreach(['12px', '14px', '16px', '20px'] as $radius)
                                            <option value="{{ $radius }}" @selected(($draft['border_radius'] ?? '16px') === $radius)>{{ $radius }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 st-field">
                                    <label for="shadow_intensity">Shadow intensity</label>
                                    <select id="shadow_intensity" name="shadow_intensity">
                                        @foreach(['soft', 'medium', 'strong'] as $level)
                                            <option value="{{ $level }}" @selected(($draft['shadow_intensity'] ?? 'medium') === $level)>{{ ucfirst($level) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" name="preset_slug" id="preset_slug" value="{{ $active_preset ?? '' }}">

                            <div class="st-actions">
                                <button type="button" class="st-btn st-btn--ghost" id="st-save-draft">Save draft</button>
                                <button type="button" class="st-btn st-btn--accent" id="st-publish">Publish theme</button>
                                <button type="button" class="st-btn st-btn--ghost" id="st-reset">Reset default</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="st-card">
                    <div class="st-card__header">Version history</div>
                    <div class="st-card__body">
                        @if($versions->isEmpty())
                            <p class="text-muted mb-0">No versions yet — publish to create the first snapshot.</p>
                        @else
                            <ul class="st-version-list">
                                @foreach($versions as $version)
                                    <li>
                                        <span>
                                            <strong>v{{ $version->version_number }}</strong>
                                            — {{ ucfirst(str_replace('_', ' ', $version->action)) }}
                                            @if($version->note)
                                                <span class="text-muted">({{ $version->note }})</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $version->created_at?->format('M j, Y g:i A') }}</small>
                                        </span>
                                        <button type="button" class="st-btn st-btn--ghost st-restore-version"
                                                data-version="{{ $version->id }}">Restore</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="st-card">
                    <div class="st-card__header">Live preview</div>
                    <div class="st-card__body p-0">
                        <div class="st-preview" id="st-live-preview">
                            <div class="st-preview__chrome">
                                <span>Sukoon Homes</span>
                                <span style="font-size:0.8125rem;opacity:0.85">Premium Property</span>
                            </div>
                            <div class="st-preview__body">
                                <div class="st-preview__card" id="st-preview-card">
                                    <div style="font-size:0.75rem;color:var(--preview-muted,#6B7280);margin-bottom:0.35rem">Featured listing</div>
                                    <div style="font-size:1.125rem;font-weight:700;margin-bottom:0.5rem">Luxury Villa — Barmer</div>
                                    <div style="font-size:0.875rem;color:var(--preview-muted,#6B7280)">4 BHK · 2,400 sq ft · Verified owner</div>
                                    <div class="st-preview__price" style="font-size:1.25rem;font-weight:700;margin:0.75rem 0">₹ 1.2 Cr</div>
                                    <button type="button" class="st-preview__btn">View property</button>
                                    <div style="margin-top:0.75rem">
                                        <a href="#" class="st-preview__link" onclick="return false">Schedule visit →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const routes = {
                draft: @json(route('appearance.theme.draft')),
                publish: @json(route('appearance.theme.publish')),
                reset: @json(route('appearance.theme.reset')),
                preset: @json(route('appearance.theme.preset')),
                restore: @json(route('appearance.theme.restore')),
            };

            const shadows = {
                soft: '0 2px 12px rgba(15, 23, 42, 0.05)',
                medium: '0 4px 20px rgba(15, 23, 42, 0.08)',
                strong: '0 8px 32px rgba(15, 23, 42, 0.14)',
            };

            function collectPayload() {
                const form = document.getElementById('st-theme-form');
                const fd = new FormData(form);
                const tokens = {};
                form.querySelectorAll('[data-token]').forEach(function (el) {
                    tokens[el.dataset.token] = el.value;
                });
                return {
                    tokens: tokens,
                    typography: fd.get('typography'),
                    border_radius: fd.get('border_radius'),
                    shadow_intensity: fd.get('shadow_intensity'),
                    preset_slug: fd.get('preset_slug') || null,
                };
            }

            function applyPreview(payload) {
                const preview = document.getElementById('st-live-preview');
                const t = payload.tokens;
                const radius = payload.border_radius || '16px';
                const shadow = shadows[payload.shadow_intensity] || shadows.medium;
                const semantic = @json($draft_semantic);

                preview.style.setProperty('--preview-sidebar', t.sidebar);
                preview.style.setProperty('--preview-section', t.section);
                preview.style.setProperty('--preview-card', t.card);
                preview.style.setProperty('--preview-border', t.border);
                preview.style.setProperty('--preview-button', t.button);
                preview.style.setProperty('--preview-hover', t.hover);
                preview.style.setProperty('--preview-link', semantic.link_on_light);
                preview.style.setProperty('--preview-primary', semantic.text_primary);
                preview.style.setProperty('--preview-muted', semantic.text_secondary);
                preview.style.setProperty('--preview-white', semantic.text_on_dark);
                preview.style.setProperty('--preview-radius', radius);
                preview.style.setProperty('--preview-shadow', shadow);
                preview.style.fontFamily = payload.typography;
                preview.style.color = semantic.text_primary;
            }

            function post(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body || {}),
                }).then(function (r) { return r.json(); });
            }

            function bindColorSync() {
                document.querySelectorAll('[data-token]').forEach(function (colorInput) {
                    const key = colorInput.dataset.token;
                    const hex = document.querySelector('.token-hex[data-for="' + key + '"]');
                    colorInput.addEventListener('input', function () {
                        if (hex) hex.value = colorInput.value.toUpperCase();
                        applyPreview(collectPayload());
                    });
                    if (hex) {
                        hex.addEventListener('change', function () {
                            if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) {
                                colorInput.value = hex.value;
                                applyPreview(collectPayload());
                            }
                        });
                    }
                });

                ['typography', 'border_radius', 'shadow_intensity'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('change', function () { applyPreview(collectPayload()); });
                });
            }

            document.getElementById('st-save-draft')?.addEventListener('click', function () {
                post(routes.draft, collectPayload()).then(function (res) {
                    alert(res.message || (res.error ? 'Failed' : 'Saved'));
                    if (!res.error) location.reload();
                });
            });

            document.getElementById('st-publish')?.addEventListener('click', function () {
                if (!confirm('Publish theme globally for frontend and admin?')) return;
                post(routes.publish, collectPayload()).then(function (res) {
                    alert(res.message || (res.error ? 'Failed' : 'Published'));
                    if (!res.error) location.reload();
                });
            });

            document.getElementById('st-reset')?.addEventListener('click', function () {
                if (!confirm('Reset to Sukoon Pure Black Luxury default and publish?')) return;
                post(routes.reset, {}).then(function (res) {
                    alert(res.message || (res.error ? 'Failed' : 'Reset'));
                    if (!res.error) location.reload();
                });
            });

            document.querySelectorAll('.st-btn-apply-preset').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    post(routes.preset, { preset_slug: btn.dataset.preset }).then(function (res) {
                        if (res.error) { alert(res.message); return; }
                        location.reload();
                    });
                });
            });

            document.querySelectorAll('.st-restore-version').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    post(routes.restore, { version_id: parseInt(btn.dataset.version, 10) }).then(function (res) {
                        alert(res.message || (res.error ? 'Failed' : 'Restored'));
                        if (!res.error) location.reload();
                    });
                });
            });

            bindColorSync();
            applyPreview(@json($draft));
        })();
    </script>
@endsection
