<button type="button" class="btn btn-sm btn-primary edit-area" data-bs-toggle="modal" data-bs-target="#editAreaModal"
    data-id="{{ $area->id }}"
    data-name="{{ e($area->name) }}"
    data-city-id="{{ (int) ($area->city_id ?? 0) }}"
    data-city="{{ e($area->city) }}"
    data-state="{{ e($area->state) }}"
    data-country="{{ e($area->country) }}"
    data-center-lat="{{ $area->center_lat }}"
    data-center-lng="{{ $area->center_lng }}"
    data-radius-meters="{{ $area->radius_meters }}"
    data-sort-order="{{ $area->sort_order }}"
    data-status="{{ (int) $area->status }}"
    data-seo-title="{{ e($area->seo_title) }}"
    data-seo-description="{{ e($area->seo_description) }}">
    {{ __('Edit') }}
</button>
<form action="{{ route('area-listing.areas.destroy', $area->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this area?') }}')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
</form>