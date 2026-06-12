<?php

namespace App\Plugins\SeoEngine\Services\Content;

interface SeoEngineAiProviderInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{success:bool,text?:string,error?:string}
     */
    public function generate(string $prompt, array $options = []): array;

    public function name(): string;
}
