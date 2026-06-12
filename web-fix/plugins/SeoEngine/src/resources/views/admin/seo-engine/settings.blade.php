@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.settings_title'))

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
<section class="section pt-2">
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'settings'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('seo-engine.settings.store') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.identity') }}</h5></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Site name</label>
                    <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings['site_name'] ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site URL</label>
                    <input type="url" name="site_url" class="form-control" value="{{ old('site_url', $settings['site_url'] ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo URL</label>
                    <input type="url" name="logo_url" class="form-control" value="{{ old('logo_url', $settings['logo_url'] ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="site_description" class="form-control" rows="2">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('seo-engine::seo_engine.same_as') }}</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-same-as">{{ __('seo-engine::seo_engine.add_url') }}</button>
            </div>
            <div class="card-body" id="same-as-list">
                @php $sameAs = old('same_as', $settings['same_as'] ?? ['']); @endphp
                @foreach($sameAs as $i => $url)
                    <div class="input-group mb-2 same-as-row">
                        <input type="url" name="same_as[]" class="form-control" value="{{ $url }}" placeholder="https://">
                        <button type="button" class="btn btn-outline-danger remove-row">&times;</button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('seo-engine::seo_engine.knows_about') }}</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-knows">{{ __('seo-engine::seo_engine.add_tag') }}</button>
            </div>
            <div class="card-body" id="knows-list">
                @php $tags = old('knows_about', $settings['knows_about'] ?? ['']); @endphp
                @foreach($tags as $tag)
                    <div class="input-group mb-2 knows-row">
                        <input type="text" name="knows_about[]" class="form-control" value="{{ $tag }}">
                        <button type="button" class="btn btn-outline-danger remove-row">&times;</button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.index_threshold') }}</label>
                    <input type="number" name="index_threshold" class="form-control" min="1" max="100"
                        value="{{ old('index_threshold', $settings['index_threshold'] ?? 3) }}" required>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('seo-engine::seo_engine.budget_bands') }}</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-band">{{ __('seo-engine::seo_engine.add_band') }}</button>
            </div>
            <div class="card-body" id="bands-list">
                @php $bands = old('budget_bands', $settings['budget_bands'] ?? []); @endphp
                @forelse($bands as $i => $band)
                    <div class="row g-2 mb-2 band-row">
                        <div class="col-md-4"><input type="text" name="budget_bands[{{ $i }}][label]" class="form-control" value="{{ $band['label'] ?? '' }}" placeholder="Label"></div>
                        <div class="col-md-3"><input type="number" name="budget_bands[{{ $i }}][min]" class="form-control" value="{{ $band['min'] ?? '' }}" placeholder="Min"></div>
                        <div class="col-md-3"><input type="number" name="budget_bands[{{ $i }}][max]" class="form-control" value="{{ $band['max'] ?? '' }}" placeholder="Max"></div>
                        <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-row">&times;</button></div>
                    </div>
                @empty
                    <div class="row g-2 mb-2 band-row">
                        <div class="col-md-4"><input type="text" name="budget_bands[0][label]" class="form-control" placeholder="Label"></div>
                        <div class="col-md-3"><input type="number" name="budget_bands[0][min]" class="form-control" placeholder="Min"></div>
                        <div class="col-md-3"><input type="number" name="budget_bands[0][max]" class="form-control" placeholder="Max"></div>
                        <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-row">&times;</button></div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('seo-engine::seo_engine.type_facets') }}</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-type-facet">{{ __('seo-engine::seo_engine.add_type_facet') }}</button>
            </div>
            <div class="card-body" id="type-facets-list">
                @php $typeFacets = old('type_facets', $settings['type_facets'] ?? ['flat', 'house', 'apartment', 'pg']); @endphp
                @forelse($typeFacets as $facet)
                    <div class="input-group mb-2 type-facet-row">
                        <input type="text" name="type_facets[]" class="form-control" value="{{ $facet }}" placeholder="flat">
                        <button type="button" class="btn btn-outline-danger remove-row">&times;</button>
                    </div>
                @empty
                    <div class="input-group mb-2 type-facet-row">
                        <input type="text" name="type_facets[]" class="form-control" placeholder="flat">
                        <button type="button" class="btn btn-outline-danger remove-row">&times;</button>
                    </div>
                @endforelse
            </div>
            <div class="card-footer text-muted small">{{ __('seo-engine::seo_engine.type_facets_help') }}</div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.schema_toggles') }}</h5></div>
            <div class="card-body row g-2">
                @foreach($schemaTypes as $type)
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="schema_toggles[{{ $type }}]" value="1"
                                id="schema-{{ $type }}"
                                @checked(old('schema_toggles.'.$type, $settings['schema_toggles'][$type] ?? true))>
                            <label class="form-check-label" for="schema-{{ $type }}">{{ $type }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.ai_content') }}</h5></div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.ai_provider') }}</label>
                    <select name="ai_provider" class="form-select">
                        <option value="gemini" @selected(old('ai_provider', $settings['ai_provider'] ?? 'gemini') === 'gemini')>Google Gemini (GEMINI_API_KEY)</option>
                        <option value="claude" @selected(old('ai_provider', $settings['ai_provider'] ?? '') === 'claude')>Anthropic Claude (SEO_AI_API_KEY)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.ai_model_claude') }}</label>
                    <input type="text" name="ai_model_claude" class="form-control" value="{{ old('ai_model_claude', $settings['ai_model_claude'] ?? 'claude-3-5-haiku-20241022') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.ai_rate_limit_ms') }}</label>
                    <input type="number" name="ai_rate_limit_ms" class="form-control" value="{{ old('ai_rate_limit_ms', $settings['ai_rate_limit_ms'] ?? 2000) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.ai_content_min_listings') }}</label>
                    <input type="number" name="ai_content_min_listings" class="form-control" min="0" max="100"
                        value="{{ old('ai_content_min_listings', $settings['ai_content_min_listings'] ?? 1) }}">
                    <div class="form-text">{{ __('seo-engine::seo_engine.ai_content_min_listings_help') }}</div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('seo-engine::seo_engine.ai_api_key') }}</label>
                    <input type="password" name="ai_api_key" class="form-control" placeholder="{{ __('seo-engine::seo_engine.ai_api_key_hint') }}" autocomplete="new-password">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('seo-engine::seo_engine.prompt_template_content') }}</label>
                    <textarea name="prompt_template_content" class="form-control font-monospace" rows="12">{{ old('prompt_template_content', $settings['prompt_template_content'] ?? '') }}</textarea>
                    <div class="form-text">{{ __('seo-engine::seo_engine.prompt_template_content_help') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.robots_txt') }}</h5></div>
            <div class="card-body">
                <textarea name="robots_txt" class="form-control font-monospace" rows="8">{{ old('robots_txt', $settings['robots_txt'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.llms_txt') }}</h5></div>
            <div class="card-body">
                <textarea name="llms_txt" class="form-control font-monospace" rows="6">{{ old('llms_txt', $settings['llms_txt'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.ai_bot_policy') }}</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Bot</th><th>Policy</th></tr></thead>
                    <tbody>
                        @foreach($aiBots as $bot)
                            @php $policy = old('ai_bot_policy.'.$bot, $settings['ai_bot_policy'][$bot] ?? 'block'); @endphp
                            <tr>
                                <td><code>{{ $bot }}</code></td>
                                <td>
                                    <select name="ai_bot_policy[{{ $bot }}]" class="form-select form-select-sm" style="max-width:140px">
                                        <option value="allow" @selected($policy === 'allow')>{{ __('seo-engine::seo_engine.allow') }}</option>
                                        <option value="block" @selected($policy === 'block')>{{ __('seo-engine::seo_engine.block') }}</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.btn_save') }}</button>
        </div>
    </form>

    <form method="post" action="{{ route('seo-engine.settings.clear-cache') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">{{ __('seo-engine::seo_engine.btn_clear_cache') }}</button>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let bandIndex = document.querySelectorAll('.band-row').length;
    document.getElementById('add-same-as')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'input-group mb-2 same-as-row';
        row.innerHTML = '<input type="url" name="same_as[]" class="form-control" placeholder="https://"><button type="button" class="btn btn-outline-danger remove-row">&times;</button>';
        document.getElementById('same-as-list').appendChild(row);
    });
    document.getElementById('add-knows')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'input-group mb-2 knows-row';
        row.innerHTML = '<input type="text" name="knows_about[]" class="form-control"><button type="button" class="btn btn-outline-danger remove-row">&times;</button>';
        document.getElementById('knows-list').appendChild(row);
    });
    document.getElementById('add-band')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 band-row';
        row.innerHTML = `
            <div class="col-md-4"><input type="text" name="budget_bands[${bandIndex}][label]" class="form-control" placeholder="Label"></div>
            <div class="col-md-3"><input type="number" name="budget_bands[${bandIndex}][min]" class="form-control" placeholder="Min"></div>
            <div class="col-md-3"><input type="number" name="budget_bands[${bandIndex}][max]" class="form-control" placeholder="Max"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-row">&times;</button></div>`;
        document.getElementById('bands-list').appendChild(row);
        bandIndex++;
    });
    document.getElementById('add-type-facet')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'input-group mb-2 type-facet-row';
        row.innerHTML = '<input type="text" name="type_facets[]" class="form-control" placeholder="flat"><button type="button" class="btn btn-outline-danger remove-row">&times;</button>';
        document.getElementById('type-facets-list').appendChild(row);
    });
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.same-as-row, .knows-row, .band-row, .type-facet-row')?.remove();
        }
    });
});
</script>
@endsection
