<?php

use App\Plugins\TrustVerification\Models\TvSetting;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TrustVerificationPermissionService::registerModule();

        if (Schema::hasTable('tv_settings')) {
            TvSetting::updateOrCreate(
                ['key' => 'admin_permissions_registered'],
                ['value' => '2026-05-24']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tv_settings')) {
            TvSetting::where('key', 'admin_permissions_registered')->delete();
        }
    }
};
