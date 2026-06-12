<?php

namespace App\Plugins\SeoEngine\Observers;

use App\Models\Property;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;

class PropertySlugObserver
{
    public function __construct(private SeoEngineRedirectService $redirects)
    {
    }

    public function updating(Property $property): void
    {
        if (! $property->isDirty('slug_id')) {
            return;
        }

        $this->redirects->recordSlugChange(
            'property',
            (int) $property->id,
            (string) $property->getOriginal('slug_id'),
            (string) $property->slug_id,
            fn (string $slug) => '/property-details/' . $slug . '/'
        );
    }
}
