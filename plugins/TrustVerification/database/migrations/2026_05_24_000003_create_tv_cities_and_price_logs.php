<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_cities', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('label', 120);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tv_package_price_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id')->index();
            $table->unsignedInteger('old_price');
            $table->unsignedInteger('new_price');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('package_id')->references('id')->on('tv_packages')->cascadeOnDelete();
        });

        DB::table('tv_cities')->insertOrIgnore([
            [
                'slug' => 'barmer',
                'label' => 'Barmer',
                'is_enabled' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_package_price_logs');
        Schema::dropIfExists('tv_cities');
    }
};
