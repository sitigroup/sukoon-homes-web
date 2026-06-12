<button type="button" class="btn btn-sm btn-primary edit-sub-area" data-bs-toggle="modal" data-bs-target="#editSubAreaModal"
    data-id="{{ $subArea->id }}"
    data-area-id="{{ $subArea->area_id }}"
    data-name="{{ e($subArea->name) }}"
    data-center-lat="{{ $subArea->center_lat }}"
    data-center-lng="{{ $subArea->center_lng }}"
    data-radius-meters="{{ $subArea->radius_meters }}"
    data-sort-order="{{ $subArea->sort_order }}"
    data-status="{{ (int) $subArea->status }}">
    {{ __('Edit') }}
</button>
<form action="{{ route('area-listing.sub-areas.destroy', $subArea->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this sub area?') }}')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
</form>