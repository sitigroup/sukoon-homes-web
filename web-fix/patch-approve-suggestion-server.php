<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php';
$c = file_get_contents($path);

if (str_contains($c, 'createMasterFromSuggestionRecord($suggestion)')) {
    echo "already patched\n";
    exit(0);
}

$start = strpos($c, '    public function approveSuggestion(Request $request, int $id)');
if ($start === false) {
    fwrite(STDERR, "approveSuggestion not found\n");
    exit(1);
}

$end = strpos($c, '    public function rejectSuggestion', $start);
$method = substr($c, $start, $end - $start);

$newMethod = <<<'PHP'
    public function approveSuggestion(Request $request, int $id)
    {
        $suggestion = $this->pendingSuggestion($id);

        if (! $suggestion) {
            return back()->with('error', __('Pending suggestion not found.'));
        }

        $master = \App\Plugins\AreaListing\Services\AreaListingService::createMasterFromSuggestionRecord($suggestion);
        if (! $master) {
            return back()->with('error', __('Could not create master area/sub-area. Ensure city and parent area are set on the suggestion.'));
        }

        DB::transaction(function () use ($suggestion, $request) {
            $this->markSuggestionReviewed($suggestion->id, 'approved', $request->input('review_note'));
            if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                \App\Plugins\AreaListing\Services\AreaListingService::propagateApprovedSuggestion($suggestion);
            }
        });

        return back()->with('success', __('Suggestion approved successfully.'));
    }

PHP;

$c = substr($c, 0, $start) . $newMethod . substr($c, $end);
file_put_contents($path, $c);
echo "replaced approveSuggestion method\n";
passthru('php -l ' . escapeshellarg($path) . ' 2>&1');
