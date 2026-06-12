<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('area_listing_suggestions')) {
            return;
        }

        Schema::table('area_listing_suggestions', function (Blueprint $table) {
            if (! Schema::hasColumn('area_listing_suggestions', 'group_token')) {
                $table->string('group_token', 36)->nullable()->after('status');
                $table->index('group_token', 'area_listing_suggestions_group_token');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('area_listing_suggestions')) {
            return;
        }

        Schema::table('area_listing_suggestions', function (Blueprint $table) {
            if (Schema::hasColumn('area_listing_suggestions', 'group_token')) {
                $table->dropIndex('area_listing_suggestions_group_token');
                $table->dropColumn('group_token');
            }
        });
    }
};
