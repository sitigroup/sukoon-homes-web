@extends('layouts.main')

@section('title', 'Trust Verification Content')

@section('content')
    <section class="section tv-admin tv-admin-page tv-cms">
        @include('trust-verification::admin.partials.nav')
        @include('trust-verification::admin.content.partials.cms-theme')

        <header class="tv-admin-header">
            <div>
                <h1 class="tv-admin-header__title">Content studio</h1>
                <p class="tv-admin-header__meta mb-0">Trust Verification CMS — edit copy, legal, email, and FAQs without code.</p>
            </div>
            <form method="post" action="{{ route('trust-verification.content.seed') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn tv-admin-btn-secondary">
                    <i class="bi bi-database-add"></i> Seed missing defaults
                </button>
            </form>
        </header>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @include('trust-verification::admin.content.partials.kpi-cards')
        @include('trust-verification::admin.content.partials.activity-widget')
        @include('trust-verification::admin.content.partials.tabs')
        @include('trust-verification::admin.content.partials.filters')

        @if($sortable ?? false)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                @if($tab === 'faq')
                    <form method="post" action="{{ route('trust-verification.content.faq.store') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm tv-cms-btn tv-cms-btn--primary">
                            <i class="bi bi-plus-lg"></i> Add FAQ
                        </button>
                    </form>
                @else
                    <span></span>
                @endif
                <p class="small text-muted mb-0"><i class="bi bi-grip-vertical"></i> Drag ⋮⋮ to reorder — saves automatically</p>
            </div>
        @endif

        @if(($blocksWithPreview ?? collect())->isEmpty())
            <div class="tv-cms-block text-center py-5">
                <p class="text-muted mb-2">No blocks match your filters.</p>
                <a href="{{ route('trust-verification.content.index', ['tab' => $tab]) }}" class="btn btn-sm tv-cms-btn tv-cms-btn--secondary">Clear filters</a>
            </div>
        @else
            @if($sortable ?? false)
                <form id="tv-cms-reorder-form" method="post" action="{{ route('trust-verification.content.reorder') }}">
                    @csrf
                    <input type="hidden" name="group_key" value="{{ $tab }}">
                    <div id="tv-cms-sortable-list">
                        @foreach($blocksWithPreview as $item)
                            @include('trust-verification::admin.content.partials.block-card', [
                                'item' => $item,
                                'sortable' => true,
                            ])
                        @endforeach
                    </div>
                    <div id="tv-cms-order-fields" class="d-none" aria-hidden="true"></div>
                </form>
            @else
                <div>
                    @foreach($blocksWithPreview as $item)
                        @include('trust-verification::admin.content.partials.block-card', [
                            'item' => $item,
                            'sortable' => false,
                        ])
                    @endforeach
                </div>
            @endif
        @endif
    </section>

    @include('trust-verification::admin.content.partials.cms-scripts')
    @if($sortable ?? false)
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" crossorigin="anonymous"></script>
        <script>
            (function () {
                var list = document.getElementById('tv-cms-sortable-list');
                var form = document.getElementById('tv-cms-reorder-form');
                if (!list || !form || typeof Sortable === 'undefined') return;

                new Sortable(list, {
                    handle: '.tv-cms-block__drag',
                    animation: 150,
                    onEnd: function () {
                        var holder = document.getElementById('tv-cms-order-fields');
                        holder.innerHTML = '';
                        list.querySelectorAll('.tv-cms-block').forEach(function (el) {
                            var hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'order[]';
                            hidden.value = el.getAttribute('data-block-id');
                            holder.appendChild(hidden);
                        });
                        form.submit();
                    }
                });
            })();
        </script>
    @endif
@endsection
