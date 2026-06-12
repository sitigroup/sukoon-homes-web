<?php

namespace App\Plugins\SeoEngine\Observers;

use App\Models\Projects;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;

class ProjectSlugObserver
{
    public function __construct(private SeoEngineRedirectService $redirects)
    {
    }

    public function updating(Projects $project): void
    {
        if (! $project->isDirty('slug_id')) {
            return;
        }

        $this->redirects->recordSlugChange(
            'project',
            (int) $project->id,
            (string) $project->getOriginal('slug_id'),
            (string) $project->slug_id,
            fn (string $slug) => '/project-details/' . $slug . '/'
        );
    }
}
