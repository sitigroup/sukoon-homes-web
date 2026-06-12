@extends('layouts.main')

@section('title', 'Edit — '.$previewHeadline)

@section('content')
    <section class="section tv-admin tv-cms">
        @include('trust-verification::admin.partials.nav')
        @include('trust-verification::admin.content.partials.cms-theme')

        <div class="mb-3">
            <a href="{{ route('trust-verification.content.index', ['tab' => $block->group_key]) }}"
               class="tv-cms-link text-decoration-none small">
                <i class="bi bi-arrow-left"></i> Back to {{ ucfirst($block->group_key) }}
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h4 class="mb-1" style="color: var(--tv-graphite); font-weight: 700;">{{ $previewHeadline }}</h4>
                <code class="small text-muted">{{ $block->content_key }}</code>
            </div>
            <span class="{{ \App\Plugins\TrustVerification\Support\TrustVerificationContentPreview::statusClass($block) }} tv-cms-status">
                {{ $block->is_active ? 'Published' : 'Draft' }}
            </span>
        </div>

        {{-- Publish flow: Draft → Preview → Publish --}}
        <div class="tv-cms-flow" aria-label="Publish workflow">
            <span class="tv-cms-flow__step {{ !$block->is_active ? 'is-current' : 'is-done' }}">
                <i class="bi bi-pencil"></i> Draft
            </span>
            <span class="tv-cms-flow__arrow">→</span>
            <span class="tv-cms-flow__step" id="tv-flow-preview">
                <i class="bi bi-eye"></i> Preview
            </span>
            <span class="tv-cms-flow__arrow">→</span>
            <span class="tv-cms-flow__step {{ $block->is_active ? 'is-current' : '' }}">
                <i class="bi bi-check2-circle"></i> Publish
            </span>
        </div>

        <div class="tv-cms-edit-split">
            <div>
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                        <strong style="color: var(--tv-graphite);">Editor</strong>
                        <span class="small text-muted">v{{ $block->version }}</span>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('trust-verification.content.update', $block) }}"
                              id="tv-cms-edit-form" class="tv-cms-live-form">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Display title</label>
                                <input type="text" name="title" class="form-control tv-cms-live"
                                       data-preview="title" value="{{ old('title', $block->title) }}">
                            </div>

                            @if($block->type === 'faq')
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Question</label>
                                    <input type="text" name="question" class="form-control tv-cms-live"
                                           data-preview="question" value="{{ old('question', $block->content_json['question'] ?? '') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Answer</label>
                                    <textarea name="answer" class="form-control tv-cms-live" rows="4"
                                              data-preview="answer">{{ old('answer', $block->content_json['answer'] ?? '') }}</textarea>
                                </div>
                            @elseif($block->type === 'testimonial')
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Customer name</label>
                                    <input type="text" name="customer_name" class="form-control tv-cms-live"
                                           data-preview="customer_name" value="{{ old('customer_name', $block->content_json['customer_name'] ?? '') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Rating (1–5)</label>
                                    <input type="number" name="rating" min="1" max="5" class="form-control tv-cms-live"
                                           data-preview="rating" value="{{ old('rating', $block->content_json['rating'] ?? 5) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Review</label>
                                    <textarea name="review" class="form-control tv-cms-live" rows="3"
                                              data-preview="review">{{ old('review', $block->content_json['review'] ?? '') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">City</label>
                                    <input type="text" name="city" class="form-control tv-cms-live"
                                           data-preview="city" value="{{ old('city', $block->content_json['city'] ?? '') }}">
                                </div>
                            @elseif($block->type === 'email')
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Subject</label>
                                    <input type="text" name="email_subject" class="form-control tv-cms-live"
                                           data-preview="email_subject" value="{{ old('email_subject', $block->content_json['subject'] ?? '') }}">
                                    <div class="form-text">Placeholders: {order_number}, {subject_name}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Body HTML</label>
                                    <textarea name="body_html" class="form-control tv-cms-live" rows="6"
                                              data-preview="body_html">{{ old('body_html', $block->content_json['body_html'] ?? '') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Body text</label>
                                    <textarea name="body_text" class="form-control tv-cms-live" rows="3"
                                              data-preview="body_text">{{ old('body_text', $block->content_json['body_text'] ?? '') }}</textarea>
                                </div>
                            @elseif($block->type === 'legal')
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Legal JSON</label>
                                    <textarea name="content_json" class="form-control font-monospace tv-cms-live" rows="14"
                                              data-preview="content_json">{{ old('content_json', json_encode($block->content_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                                </div>
                            @elseif($block->type === 'json')
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">JSON</label>
                                    <textarea name="content_json" class="form-control font-monospace tv-cms-live" rows="10"
                                              data-preview="content_json">{{ old('content_json', json_encode($block->content_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Content</label>
                                    <textarea name="content" class="form-control tv-cms-live" rows="8"
                                              data-preview="content">{{ old('content', $block->content) }}</textarea>
                                    <div class="form-text">Plain text (HTML/scripts stripped on save).</div>
                                </div>
                            @endif

                            @if(!in_array($block->group_key, ['faq', 'testimonials'], true))
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Sort order</label>
                                    <input type="number" name="sort_order" class="form-control"
                                           value="{{ old('sort_order', $block->sort_order) }}">
                                </div>
                            @endif

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn tv-cms-btn tv-cms-btn--primary">
                                    <i class="bi bi-save"></i> Save draft
                                </button>
                                <button type="button" class="btn tv-cms-btn tv-cms-btn--secondary"
                                        data-bs-toggle="modal" data-bs-target="#tvCmsPreviewModal">
                                    <i class="bi bi-eye"></i> Full preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3" style="border-radius: 14px;">
                    <div class="card-body">
                        <h6 class="small text-uppercase text-muted fw-bold mb-2">Version history</h6>
                        <p class="small text-muted mb-2">
                            Current: <strong>v{{ $block->version }}</strong>.
                            Point-in-time restore uses factory reset; audit log tracks each save.
                        </p>
                        <div class="tv-cms-version">
                            @for($v = 1; $v <= max(1, (int) $block->version); $v++)
                                <span class="tv-cms-version__chip {{ $v === (int) $block->version ? 'is-current' : '' }}">v{{ $v }}</span>
                            @endfor
                        </div>
                        @if($versionHistory->isNotEmpty())
                            <ul class="list-unstyled small mt-3 mb-0">
                                @foreach($versionHistory->take(6) as $log)
                                    <li class="py-1 border-bottom">
                                        <span class="text-muted">{{ $log->created_at?->format('d M H:i') }}</span>
                                        — {{ str_replace('_', ' ', $log->action) }}
                                        @if(!empty($log->metadata['version']))
                                            <span class="badge bg-light text-dark">v{{ $log->metadata['version'] }}</span>
                                        @endif
                                        <span class="text-muted">· {{ $log->actorLabel() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <form method="post" action="{{ route('trust-verification.content.reset', $block) }}" class="mt-3"
                              onsubmit="return confirm('Reset this block to factory default?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-arrow-counterclockwise"></i> Restore factory default
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div>
                <div class="tv-cms-preview-panel sticky-lg-top" style="top: 5rem;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-bold" style="color: var(--tv-graphite);">Live preview</h6>
                        <span class="small text-muted">Updates as you type</span>
                    </div>
                    <div id="tv-cms-live-preview" class="tv-cms-preview-live" data-block-type="{{ $block->type }}">
                        @if($block->type === 'faq')
                            <p class="fw-semibold mb-2" id="pv-question">{{ $block->content_json['question'] ?? '' }}</p>
                            <p class="text-muted mb-0 small" id="pv-answer">{{ $block->content_json['answer'] ?? '' }}</p>
                        @elseif($block->type === 'email')
                            <p class="small text-muted mb-1">Subject</p>
                            <p class="fw-semibold mb-3" id="pv-email_subject">{{ $block->content_json['subject'] ?? '' }}</p>
                            <div id="pv-body_html" class="small">{!! $block->content_json['body_html'] ?? '' !!}</div>
                        @else
                            <p class="mb-0" id="pv-content">{{ $previewSnippet }}</p>
                        @endif
                    </div>
                    <hr class="my-3">
                    <p class="small text-muted mb-1">Raw export</p>
                    <pre class="small mb-0 tv-cms-modal-preview" id="tv-cms-raw-preview" style="max-height: 180px;">{{ is_array($preview) ? json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $preview }}</pre>
                </div>

                <div class="card border-0 shadow-sm mt-3" style="border-radius: 14px;">
                    <div class="card-body">
                        <h6 class="small text-uppercase text-muted fw-bold mb-3">Publish</h6>
                        <form method="post" action="{{ route('trust-verification.content.publish', $block) }}">
                            @csrf
                            <button type="submit" class="btn w-100 tv-cms-btn {{ $block->is_active ? 'tv-cms-btn--secondary' : 'tv-cms-btn--primary' }}">
                                @if($block->is_active)
                                    <i class="bi bi-eye-slash"></i> Unpublish (move to draft)
                                @else
                                    <i class="bi bi-check2-circle"></i> Publish to site
                                @endif
                            </button>
                        </form>
                        <p class="small text-muted mt-2 mb-0">
                            Save your edits first, preview, then publish when ready for the public API.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="tvCmsPreviewModal" tabindex="-1" aria-labelledby="tvCmsPreviewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="border-radius: 14px;">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="tvCmsPreviewModalLabel">Preview — {{ $previewHeadline }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="tv-cms-modal-body" class="tv-cms-modal-preview"></div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn tv-cms-btn tv-cms-btn--secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('trust-verification::admin.content.partials.cms-edit-scripts')
@endsection
