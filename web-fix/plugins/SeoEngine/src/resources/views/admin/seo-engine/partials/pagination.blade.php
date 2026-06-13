@if($paginator->hasPages())
    <div class="{{ $wrapperClass ?? 'card-footer d-flex justify-content-center py-3' }}">
        {{ $paginator->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
@endif
