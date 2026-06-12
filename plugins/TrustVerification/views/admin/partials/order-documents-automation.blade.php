@if($tvPermissions['documents'] ?? false)
    @include('trust-verification::admin.partials.tv-od-accordion-start', [
        'icon' => 'bi-folder2-open',
        'title' => 'Uploaded documents',
        'helper' => 'ID proofs and supporting files',
        'meta' => (string) $order->documents->count(),
    ])
        @forelse($order->documents as $doc)
            @php
                $fileAvailable = \App\Plugins\TrustVerification\Services\TrustVerificationPiiRetentionService::documentFileAvailable($doc);
                $safeName = \App\Plugins\TrustVerification\Services\TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename($doc->original_name);
                $mimeLabel = strtoupper(str_replace('image/', '', (string) $doc->mime_type));
                $sizeKb = number_format($doc->size_bytes / 1024, 1);
                $docLabel = $documentTypes[$doc->doc_type] ?? $doc->doc_type;
            @endphp
            <div class="tv-od-doc-card">
                <div>
                    <p class="tv-od-doc-card__title">{{ $docLabel }}</p>
                    <p class="tv-od-doc-card__meta">{{ $sizeKb }} KB · {{ $mimeLabel }} · {{ $safeName }}</p>
                    @if($doc->deleted_at)
                        <p class="tv-od-doc-card__meta mb-0">File removed {{ $doc->deleted_at->format('d M Y') }}</p>
                    @endif
                </div>
                <div class="tv-od-doc-card__actions">
                    @if($fileAvailable)
                        @php $downloadUrl = route('trust-verification.orders.documents.download', [$order, $doc]); @endphp
                        <a href="{{ $downloadUrl }}" target="_blank" rel="noopener" class="btn tv-od-btn-secondary">Preview</a>
                        <a href="{{ $downloadUrl }}" class="btn tv-od-btn-secondary">Download</a>
                    @else
                        <span class="text-muted align-self-center">Unavailable</span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No documents uploaded yet.</p>
        @endforelse
        @if($hasActiveDocuments ?? false)
            <form method="post" action="{{ route('trust-verification.orders.documents.delete', $order) }}" class="mt-3"
                  onsubmit="return confirm('Delete all uploaded document files for this order? The order record, payments, and audit history will be kept.');">
                @csrf
                <button type="submit" class="btn tv-od-btn-secondary">Delete all documents</button>
            </form>
        @endif
    @include('trust-verification::admin.partials.tv-od-accordion-end')
@endif

@if($tvPermissions['update'] ?? false)
    @include('trust-verification::admin.partials.tv-od-accordion-start', [
        'icon' => 'bi-robot',
        'title' => 'Automation',
        'helper' => 'Rules engine and run history',
        'muted' => true,
    ])
        @if(!($automationSettings['automation_enabled'] ?? false))
            <p class="text-muted mb-0">Automation is disabled — enable under <a href="{{ route('trust-verification.automation.index') }}">Automation</a>.</p>
        @else
            <p class="mb-3">Provider: <strong>{{ $automationSettings['providers'][$automationSettings['automation_provider'] ?? 'rules'] ?? 'rules' }}</strong>
                @if($order->automation_status)
                    · <span class="tv-od-chip tv-od-chip--muted">{{ $order->automation_status }}</span>
                @endif
            </p>
            @if($order->automationRuns->isEmpty())
                <p class="text-muted mb-3">No runs yet.</p>
            @else
                <ul class="list-unstyled mb-3">
                    @foreach($order->automationRuns->take(5) as $run)
                        <li class="mb-2">
                            {{ $run->created_at?->format('d M H:i') }} · {{ $run->provider }} ·
                            @if($run->status === 'completed')<span class="tv-od-chip tv-od-chip--success">ok</span>@elseif($run->status === 'failed')<span class="tv-od-chip tv-od-chip--danger" title="{{ $run->error_message }}">failed</span>@else<span class="tv-od-chip tv-od-chip--muted">{{ $run->status }}</span>@endif
                            · {{ $run->checks_updated }} checks
                        </li>
                    @endforeach
                </ul>
            @endif
            <form method="post" action="{{ route('trust-verification.orders.automation.run', $order) }}" class="m-0">
                @csrf
                <button type="submit" class="btn tv-od-btn-save">Run automation now</button>
            </form>
        @endif
    @include('trust-verification::admin.partials.tv-od-accordion-end')
@endif
