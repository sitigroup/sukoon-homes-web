<?php

namespace App\Plugins\SeoEngine\Tests;

use App\Plugins\SeoEngine\Models\SeoEngineRedirect;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoEngineRedirectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'app/Plugins/SeoEngine/database/migrations']);
    }

    public function test_chain_flattening_a_to_b_to_c_becomes_a_to_c(): void
    {
        $service = app(SeoEngineRedirectService::class);

        $service->upsertRedirect('/property-details/a/', '/property-details/b/', 301);
        $service->upsertRedirect('/property-details/b/', '/property-details/c/', 301);

        $rowA = SeoEngineRedirect::query()->where('from_path', '/property-details/a/')->first();
        $this->assertSame('/property-details/c/', $rowA->to_path);
    }

    public function test_loop_prevention(): void
    {
        $service = app(SeoEngineRedirectService::class);

        $service->upsertRedirect('/property-details/x/', '/property-details/y/', 301);
        $blocked = $service->upsertRedirect('/property-details/y/', '/property-details/x/', 301);

        $this->assertNull($blocked);
    }

    public function test_hit_increment(): void
    {
        $service = app(SeoEngineRedirectService::class);
        $redirect = $service->upsertRedirect('/property-details/old/', '/property-details/new/', 301);
        $this->assertNotNull($redirect);

        $service->incrementHit($redirect);
        $this->assertSame(1, $redirect->fresh()->hits);
    }
}
