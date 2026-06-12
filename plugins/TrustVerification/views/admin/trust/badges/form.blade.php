@extends('layouts.main')
@section('title') Edit badge @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.trust-nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">{{ $badge->name }}</h1>
            <p class="tv-admin-header__meta mb-0"><code>{{ $badge->slug }}</code></p>
        </div>
        <a href="{{ route('trust-verification.trust.badges.index') }}" class="tv-link-back">All badges</a>
    </header>

    <div class="tv-admin-card">
        <div class="tv-admin-card__body">
            <form method="post" action="{{ route('trust-verification.trust.badges.update', $badge) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $badge->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority" class="form-control" value="{{ old('priority', $badge->priority) }}" min="0" max="999">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Icon (Bootstrap Icons)</label>
                        <input type="text" name="icon" class="form-control" value="{{ old('icon', $badge->icon) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $badge->description) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" class="form-control" value="{{ old('color', $badge->color) }}">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="active" value="1" class="form-check-input" id="active" @checked(old('active', $badge->active))>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="auto_assign" value="1" class="form-check-input" id="auto_assign" @checked(old('auto_assign', $badge->auto_assign))>
                            <label class="form-check-label" for="auto_assign">Auto assign</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn tv-admin-btn-primary">Save badge</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
