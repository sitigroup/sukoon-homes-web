<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>{{ __('whatsapp::whatsapp.batch_recipient') }}</th>
                @if(($type ?? '') === 'renewal')
                    <th>{{ __('whatsapp::whatsapp.context_agreement') }}</th>
                    <th>{{ __('whatsapp::whatsapp.batch_days_before') }}</th>
                @else
                    <th>{{ __('whatsapp::whatsapp.context_property') }}</th>
                    <th>{{ __('whatsapp::whatsapp.batch_rent_amount') }}</th>
                @endif
                <th>{{ __('whatsapp::whatsapp.phone') }}</th>
                <th>{{ __('whatsapp::whatsapp.batch_status') }}</th>
                <th>{{ __('whatsapp::whatsapp.batch_reason') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="{{ ($row['status'] ?? '') === 'eligible' ? '' : 'table-light' }}">
                    <td>{{ $row['label'] ?? '—' }}</td>
                    @if(($type ?? '') === 'renewal')
                        <td>
                            <code>{{ $row['agreement_number'] ?? '' }}</code>
                            <div class="small text-muted">{{ $row['end_date'] ?? '' }}</div>
                        </td>
                        <td>{{ $row['days_before_end'] ?? '—' }}</td>
                    @else
                        <td>{{ $row['property_label'] ?? '—' }}</td>
                        <td>{{ $row['rent_amount'] ?? '—' }}</td>
                    @endif
                    <td><code>{{ $row['phone'] ?: '—' }}</code></td>
                    <td>
                        @if(($row['status'] ?? '') === 'eligible')
                            <span class="badge bg-success">{{ __('whatsapp::whatsapp.batch_will_send') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('whatsapp::whatsapp.batch_status_skipped') }}</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $row['skip_reason'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted text-center py-4">{{ __('whatsapp::whatsapp.batch_no_rows') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
