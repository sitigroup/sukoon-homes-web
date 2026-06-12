<?php
/**
 * Use AreaListingService::createMasterFromSuggestionRecord in approveSuggestion.
 */
$adminPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php';
$c = file_get_contents($adminPath);

$needle = 'AreaListingService::createMasterFromSuggestionRecord';
if (str_contains($c, $needle)) {
    echo "approveSuggestion already uses service create\n";
} else {
    $old = <<<'PHP'
        DB::transaction(function () use ($suggestion, $request) {
            if ($suggestion->type === 'area') {
                $context = $this->resolveAreaContext(Request::create('/', 'POST', [
                    'name' => $suggestion->name,
                    'city' => $suggestion->city,
                    'state' => $suggestion->state,
                    'country' => $suggestion->country ?: 'India',
                ]));
                Area::updateOrCreate(
                    [
                        'city_id' => $context['city_id'],
                        'normalized_name' => $suggestion->normalized_name,
                    ],
                    array_merge($context, [
                        'name' => $this->cleanName($suggestion->name),
                        'slug' => Str::slug($this->cleanName($suggestion->name)),
                        'normalized_name' => $suggestion->normalized_name,
                        'status' => true,
                        'workflow_status' => 'active',
                        'archived_at' => null,
                    ])
                );
            } else {
                SubArea::updateOrCreate(
                    [
                        'area_id' => $suggestion->area_id,
                        'normalized_name' => $suggestion->normalized_name,
                    ],
                    [
                        'area_id' => $suggestion->area_id,
                        'name' => $this->cleanName($suggestion->name),
                        'slug' => Str::slug($this->cleanName($suggestion->name)),
                        'normalized_name' => $suggestion->normalized_name,
                        'status' => true,
                        'workflow_status' => 'active',
                        'archived_at' => null,
                    ]
                );
            }

            $this->markSuggestionReviewed($suggestion->id, 'approved', $request->input('review_note'));
PHP;

    $new = <<<'PHP'
        $master = \App\Plugins\AreaListing\Services\AreaListingService::createMasterFromSuggestionRecord($suggestion);
        if (! $master) {
            return back()->with('error', __('Could not create master area/sub-area. Ensure city and parent area are set on the suggestion.'));
        }

        DB::transaction(function () use ($suggestion, $request) {
            $this->markSuggestionReviewed($suggestion->id, 'approved', $request->input('review_note'));
PHP;

    if (! str_contains($c, $old)) {
        echo "WARN: approveSuggestion block not found — manual update needed\n";
    } else {
        $c = str_replace($old, $new, $c);
        file_put_contents($adminPath, $c);
        echo "patched approveSuggestion\n";
    }
}

passthru('php -l ' . escapeshellarg($adminPath) . ' 2>&1');
