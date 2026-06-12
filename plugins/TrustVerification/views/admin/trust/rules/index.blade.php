@extends('layouts.main')
@section('title') Trust rules @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.trust-nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Trust score rules</h1>
            <p class="tv-admin-header__meta mb-0">Point weights (cap 100). Manual adjustment overrides on customer profile.</p>
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="tv-admin-card">
        <div class="tv-admin-card__body">
            <form method="post" action="{{ route('trust-verification.trust.rules.update') }}">
                @csrf
                <div class="row g-3">
                    @foreach(['identity' => 'Identity verified', 'address' => 'Address verified', 'reference' => 'Reference verified', 'police' => 'Police verification', 'documents' => 'Documents verified', 'admin_approval' => 'Admin approval', 'no_rejected' => 'No rejected checks'] as $key => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }}</label>
                            <input type="number" name="{{ $key }}" class="form-control" value="{{ old($key, $weights[$key] ?? 0) }}" min="0" max="100">
                        </div>
                    @endforeach
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="trust_public_profile_enabled" value="1" class="form-check-input" id="pub"
                                   @checked(old('trust_public_profile_enabled', $publicEnabled))>
                            <label class="form-check-label" for="pub">Show trust score publicly on profiles and listings</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn tv-admin-btn-primary">Save rules</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
