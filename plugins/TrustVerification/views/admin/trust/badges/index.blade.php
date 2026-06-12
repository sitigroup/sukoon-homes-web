@extends('layouts.main')
@section('title') Trust Badges @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.trust-nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Trust badges</h1>
            <p class="tv-admin-header__meta mb-0">Catalog, priority, auto-assign rules, icons.</p>
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="tv-admin-card">
        <div class="tv-admin-card__body--flush p-0">
            <div class="table-responsive">
                <table class="table tv-admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Auto</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($badges as $badge)
                            <tr>
                                <td>{{ $badge->priority }}</td>
                                <td><i class="bi {{ $badge->icon }} me-1"></i><strong>{{ $badge->name }}</strong></td>
                                <td><code>{{ $badge->slug }}</code></td>
                                <td>{{ $badge->auto_assign ? 'Yes' : 'Manual' }}</td>
                                <td>{{ $badge->active ? 'Yes' : 'No' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('trust-verification.trust.badges.edit', $badge) }}" class="btn btn-sm tv-admin-btn-secondary">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
