<?php

namespace App\Plugins\SeoEngine\Observers;

use App\Models\Article;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;

class ArticleSlugObserver
{
    public function __construct(private SeoEngineRedirectService $redirects)
    {
    }

    public function updating(Article $article): void
    {
        if (! $article->isDirty('slug_id')) {
            return;
        }

        $this->redirects->recordSlugChange(
            'article',
            (int) $article->id,
            (string) $article->getOriginal('slug_id'),
            (string) $article->slug_id,
            fn (string $slug) => '/article-details/' . $slug . '/'
        );
    }
}
