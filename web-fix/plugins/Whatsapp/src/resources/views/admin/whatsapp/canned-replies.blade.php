@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.canned_title'))

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
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'canned'])

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="alert alert-light border mb-3">
        {{ __('whatsapp::whatsapp.canned_help') }}
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.canned_add') }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.canned.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">{{ __('whatsapp::whatsapp.canned_label') }}</label>
                    <input type="text" name="title" class="form-control" required maxlength="120">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('whatsapp::whatsapp.canned_sort') }}</label>
                    <input type="number" name="sort_order" class="form-control" value="0" min="0" max="9999">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" checked>
                        <label class="form-check-label">{{ __('whatsapp::whatsapp.enabled') }}</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('whatsapp::whatsapp.canned_body_en') }}</label>
                    <textarea name="body_en" class="form-control" rows="2" required maxlength="4096"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('whatsapp::whatsapp.canned_body_hi') }}</label>
                    <textarea name="body_hi" class="form-control" rows="2" maxlength="4096"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm text-white" style="background:#1F2937;border-color:#1F2937;">{{ __('whatsapp::whatsapp.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.canned_list') }}</h5>
        </div>
        <div class="card-body">
            @forelse($replies as $reply)
                <form method="POST" action="{{ route('whatsapp.canned.update', $reply) }}" class="border rounded p-3 mb-3">
                    @csrf
                    @method('PUT')
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('whatsapp::whatsapp.canned_label') }}</label>
                            <input type="text" name="title" class="form-control form-control-sm" value="{{ $reply->title }}" required maxlength="120">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('whatsapp::whatsapp.canned_sort') }}</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $reply->sort_order }}" min="0" max="9999">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($reply->enabled)>
                                <label class="form-check-label small">{{ __('whatsapp::whatsapp.enabled') }}</label>
                            </div>
                        </div>
                        <div class="col-md-5 d-flex align-items-end justify-content-end gap-2">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp::whatsapp.save') }}</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ __('whatsapp::whatsapp.canned_body_en') }}</label>
                            <textarea name="body_en" class="form-control form-control-sm" rows="2" required maxlength="4096">{{ $reply->body_en }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ __('whatsapp::whatsapp.canned_body_hi') }}</label>
                            <textarea name="body_hi" class="form-control form-control-sm" rows="2" maxlength="4096">{{ $reply->body_hi }}</textarea>
                        </div>
                    </div>
                </form>
                <form method="POST" action="{{ route('whatsapp.canned.destroy', $reply) }}" class="mb-4 ms-1" onsubmit="return confirm('{{ __('whatsapp::whatsapp.canned_delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('whatsapp::whatsapp.delete') }} — {{ $reply->title }}</button>
                </form>
            @empty
                <p class="text-muted mb-0">{{ __('whatsapp::whatsapp.canned_empty') }}</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
