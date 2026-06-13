@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.leads_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'leads'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.lead_status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="new" @selected($status === 'new')>New</option>
                        <option value="contacted" @selected($status === 'contacted')>Contacted</option>
                        <option value="closed" @selected($status === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.lead_area_id') }}</label>
                    <select name="area_id" class="form-select">
                        <option value="">All areas</option>
                        @foreach($areaIds as $id)
                            <option value="{{ $id }}" @selected((string)$areaId === (string)$id)>Area #{{ $id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.filter') }}</button>
                    <a href="{{ route('seo-engine.leads.export', request()->only(['status'])) }}" class="btn btn-outline-secondary">{{ __('seo-engine::seo_engine.export_csv') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Requirement</th>
                        <th>Source</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td class="text-nowrap">{{ optional($lead->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $lead->name }}</td>
                            <td><a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a></td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($lead->requirement, 80) }}</td>
                            <td><code class="small">{{ $lead->source_path }}</code></td>
                            <td>{{ $lead->form_type }}</td>
                            <td>
                                <form method="post" action="{{ route('seo-engine.leads.update', $lead) }}" class="d-flex gap-1">
                                    @csrf @method('PUT')
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="new" @selected($lead->status === 'new')>New</option>
                                        <option value="contacted" @selected($lead->status === 'contacted')>Contacted</option>
                                        <option value="closed" @selected($lead->status === 'closed')>Closed</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">{{ __('seo-engine::seo_engine.leads_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('seo-engine::admin.seo-engine.partials.pagination', ['paginator' => $leads])
    </div>
</section>
@endsection
