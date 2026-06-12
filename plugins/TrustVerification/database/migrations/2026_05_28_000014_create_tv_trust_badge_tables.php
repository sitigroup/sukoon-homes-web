<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_trust_badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('active')->default(true);
            $table->boolean('auto_assign')->default(true);
            $table->string('color', 32)->default('#111827');
            $table->string('icon', 64)->default('bi-shield-check');
            $table->timestamps();

            $table->index(['active', 'priority']);
        });

        Schema::create('tv_trust_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->unsignedTinyInteger('trust_score')->default(0);
            $table->smallInteger('manual_adjustment')->default(0);
            $table->json('score_breakdown_json')->nullable();
            $table->boolean('public_visible')->default(true);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index('trust_score');
            $table->index('public_visible');
        });

        Schema::create('tv_customer_badges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreignId('badge_id')->constrained('tv_trust_badges')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->boolean('auto_assigned')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'badge_id']);
        });

        $now = now();
        $badges = [
            ['slug' => 'identity_verified', 'name' => 'Identity Verified', 'description' => 'Government ID verified successfully.', 'priority' => 10, 'icon' => 'bi-person-badge', 'color' => '#111827'],
            ['slug' => 'address_verified', 'name' => 'Address Verified', 'description' => 'Current or property address confirmed.', 'priority' => 20, 'icon' => 'bi-geo-alt', 'color' => '#1F2937'],
            ['slug' => 'reference_verified', 'name' => 'Reference Verified', 'description' => 'Landlord or employer references verified.', 'priority' => 30, 'icon' => 'bi-telephone', 'color' => '#111827'],
            ['slug' => 'police_verified', 'name' => 'Police Verified', 'description' => 'Police verification completed.', 'priority' => 40, 'icon' => 'bi-building', 'color' => '#111827'],
            ['slug' => 'document_verified', 'name' => 'Document Verified', 'description' => 'Required documents reviewed.', 'priority' => 50, 'icon' => 'bi-folder-check', 'color' => '#374151'],
            ['slug' => 'tenant_verified', 'name' => 'Tenant Verified', 'description' => 'Tenant verification order completed.', 'priority' => 60, 'icon' => 'bi-house-door', 'color' => '#111827'],
            ['slug' => 'owner_verified', 'name' => 'Owner Verified', 'description' => 'Owner verification order completed.', 'priority' => 70, 'icon' => 'bi-key', 'color' => '#111827'],
            ['slug' => 'trusted_tenant', 'name' => 'Trusted Tenant', 'description' => 'High trust score as a verified tenant.', 'priority' => 80, 'icon' => 'bi-shield-check', 'color' => '#111827'],
            ['slug' => 'trusted_owner', 'name' => 'Trusted Owner', 'description' => 'High trust score as a verified owner.', 'priority' => 90, 'icon' => 'bi-shield-fill-check', 'color' => '#111827'],
            ['slug' => 'premium_verified', 'name' => 'Premium Verified', 'description' => 'Premium Sukoon verification tier.', 'priority' => 100, 'icon' => 'bi-award', 'color' => '#111827'],
            ['slug' => 'admin_approved', 'name' => 'Admin Approved', 'description' => 'Manually approved by Sukoon operations.', 'priority' => 110, 'icon' => 'bi-patch-check', 'color' => '#111827', 'auto_assign' => false],
            ['slug' => 'sukoon_elite_verified', 'name' => 'Sukoon Elite Verified', 'description' => 'Elite trust — top verification standing.', 'priority' => 120, 'icon' => 'bi-stars', 'color' => '#111827'],
        ];

        foreach ($badges as $row) {
            DB::table('tv_trust_badges')->insert([
                'slug' => $row['slug'],
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'priority' => $row['priority'],
                'active' => true,
                'auto_assign' => $row['auto_assign'] ?? true,
                'color' => $row['color'],
                'icon' => $row['icon'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('tv_settings')) {
            $settings = [
                ['key' => 'trust_public_profile_enabled', 'value' => '1'],
                ['key' => 'trust_score_weights', 'value' => json_encode([
                    'identity' => 20,
                    'address' => 15,
                    'reference' => 20,
                    'police' => 25,
                    'documents' => 10,
                    'admin_approval' => 5,
                    'no_rejected' => 5,
                ])],
            ];
            foreach ($settings as $setting) {
                if (! DB::table('tv_settings')->where('key', $setting['key'])->exists()) {
                    DB::table('tv_settings')->insert([
                        'key' => $setting['key'],
                        'value' => $setting['value'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_customer_badges');
        Schema::dropIfExists('tv_trust_scores');
        Schema::dropIfExists('tv_trust_badges');
    }
};
