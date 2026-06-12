@extends('layouts.main')

@section('title'){{ __('Manage Sub Areas') }}@endsection

@section('page-title')
<div class="page-title">
    <div class="row">
        <div class="col-12 col-md-6 order-md-1 order-last"><h4>@yield('title')</h4></div>
    </div>
</div>
@endsection

@section('content')
<section class="section">
    <div class="card">
        <div class="card-header"><h5>{{ __('Add Sub Area') }}</h5></div>
        <div class="card-body">
            <form action="{{ route('area-listing.sub-areas.store') }}" method="POST">
                @csrf

<div class="row">
    <div class="col-md-4 form-group mandatory">
        <label>{{ __('Area') }}</label>
        <select name="area_id" class="form-control" required>
            @foreach($areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }} - {{ $area->city }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group mandatory">
        <label>{{ __('Sub Area Name') }}</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Center Latitude') }}</label>
        <input type="number" step="any" name="center_lat" class="form-control">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Center Longitude') }}</label>
        <input type="number" step="any" name="center_lng" class="form-control">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Radius Meters') }}</label>
        <input type="number" name="radius_meters" class="form-control">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Sort Order') }}</label>
        <input type="number" name="sort_order" class="form-control" value="0">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Status') }}</label>
        <select name="status" class="form-control">
            <option value="1">{{ __('Enabled') }}</option>
            <option value="0">{{ __('Disabled') }}</option>
        </select>
    </div>
</div>

                <button class="btn btn-primary" type="submit">{{ __('Save Sub Area') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table-light" id="table_list" data-toggle="table" data-url="{{ route('area-listing.sub-areas.data') }}"
                data-side-pagination="server" data-pagination="true" data-search="true" data-show-refresh="true"
                data-show-columns="true" data-sort-name="sort_order" data-sort-order="asc">
                <thead>
                    <tr>
                        <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                        <th data-field="name" data-sortable="true">{{ __('Sub Area') }}</th>
                        <th data-field="area_name" data-sortable="true">{{ __('Area') }}</th>
                        <th data-field="city">{{ __('City') }}</th>
                        <th data-field="radius_meters">{{ __('Radius') }}</th>
                        <th data-field="status">{{ __('Status') }}</th>
                        <th data-field="operate">{{ __('Action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="editSubAreaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editSubAreaForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Sub Area') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

<div class="row">
    <div class="col-md-4 form-group mandatory">
        <label>{{ __('Area') }}</label>
        <select name="area_id" class="form-control" required>
            @foreach($areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }} - {{ $area->city }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group mandatory">
        <label>{{ __('Sub Area Name') }}</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Center Latitude') }}</label>
        <input type="number" step="any" name="center_lat" class="form-control">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Center Longitude') }}</label>
        <input type="number" step="any" name="center_lng" class="form-control">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Radius Meters') }}</label>
        <input type="number" name="radius_meters" class="form-control">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Sort Order') }}</label>
        <input type="number" name="sort_order" class="form-control" value="0">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ __('Status') }}</label>
        <select name="status" class="form-control">
            <option value="1">{{ __('Enabled') }}</option>
            <option value="0">{{ __('Disabled') }}</option>
        </select>
    </div>
</div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).on('click', '.edit-sub-area', function () {
    const btn = $(this);
    const form = $('#editSubAreaForm');
    form.attr('action', '{{ url('area-listing/sub-areas') }}/' + btn.data('id'));
    form.find('[name=area_id]').val(String(btn.data('area-id')));
    form.find('[name=name]').val(btn.data('name'));
    form.find('[name=center_lat]').val(btn.data('center-lat'));
    form.find('[name=center_lng]').val(btn.data('center-lng'));
    form.find('[name=radius_meters]').val(btn.data('radius-meters'));
    form.find('[name=sort_order]').val(btn.data('sort-order'));
    form.find('[name=status]').val(String(btn.data('status')));
});
</script>
@endsection
