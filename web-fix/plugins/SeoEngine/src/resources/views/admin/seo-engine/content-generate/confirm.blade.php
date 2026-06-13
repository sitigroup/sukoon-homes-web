@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.content_generate_confirm_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'content-generate'])

    <div class="alert alert-warning">
        <h5 class="alert-heading mb-2">
            {{ __('seo-engine::seo_engine.content_generate_confirm_heading', ['count' => $pages->count()]) }}
        </h5>
        @if($aiCount > 0)
            <p class="mb-1">{{ __('seo-engine::seo_engine.content_generate_confirm_ai', ['count' => $aiCount, 'provider' => $provider, 'min' => $minListings]) }}</p>
        @endif
        @if($fallbackCount > 0)
            <p class="mb-0">{{ __('seo-engine::seo_engine.content_generate_confirm_fallback', ['count' => $fallbackCount]) }}</p>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.content_generate_confirm_list') }}</h5></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Path</th>
                        <th>Listings</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pages as $page)
                        @php $eligibleAi = (int) $page->listing_count >= $minListings; @endphp
                        <tr>
                            <td><code>{{ $page->path }}</code></td>
                            <td>{{ $page->listing_count }}</td>
                            <td>{{ $eligibleAi ? $provider : 'Template fallback' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="{{ route('seo-engine.content-generate.run') }}">
        @csrf
        <input type="hidden" name="confirm_token" value="{{ $token }}">

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.content_generate_prompt') }}</h5></div>
            <div class="card-body">
                <textarea name="prompt_template" class="form-control font-monospace" rows="12">{{ old('prompt_template', $promptTemplate) }}</textarea>
                <div class="form-text">{{ __('seo-engine::seo_engine.content_generate_prompt_help') }}</div>
            </div>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="confirmed" value="1" class="form-check-input" id="confirmed" required>
            <label class="form-check-label" for="confirmed">{{ __('seo-engine::seo_engine.content_generate_confirm_checkbox') }}</label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger">{{ __('seo-engine::seo_engine.content_generate_proceed') }}</button>
            <a href="{{ route('seo-engine.content-generate.index') }}" class="btn btn-outline-secondary">{{ __('seo-engine::seo_engine.content_generate_back') }}</a>
        </div>
    </form>
</section>
@endsection
