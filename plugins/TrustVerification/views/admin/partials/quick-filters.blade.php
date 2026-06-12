@php
    $quickFilters = [
        ['label' => 'All', 'params' => []],
        ['label' => 'Pending payment', 'params' => ['payment_status' => 'pending']],
        ['label' => 'Paid — work queue', 'params' => ['preset' => 'work_queue']],
        ['label' => 'Submitted', 'params' => ['status' => 'submitted']],
        ['label' => 'In progress', 'params' => ['status' => 'in_progress']],
        ['label' => 'Overdue SLA', 'params' => ['overdue' => 1]],
        ['label' => 'Needs report', 'params' => ['preset' => 'needs_report']],
        ['label' => 'Has PDF', 'params' => ['has_report' => '1']],
        ['label' => 'Completed', 'params' => ['status' => 'completed']],
        ['label' => 'Cancelled', 'params' => ['preset' => 'cancelled']],
    ];

    $isQuickActive = function (array $params) use ($filters) {
        if ($params === []) {
            return empty(array_filter($filters ?? []));
        }
        foreach ($params as $k => $v) {
            if ((string) ($filters[$k] ?? '') !== (string) $v) {
                return false;
            }
        }
        return true;
    };
@endphp

<div class="tv-admin-filter tv-filter--chips">
    <div class="tv-quick-filters">
        <span class="tv-quick-filters__label">Quick filters</span>
        @foreach($quickFilters as $qf)
            <a href="{{ route('trust-verification.index', $qf['params']) }}"
               class="tv-chip-filter {{ $isQuickActive($qf['params']) ? 'is-active' : '' }}">
                {{ $qf['label'] }}
            </a>
        @endforeach
    </div>
</div>
