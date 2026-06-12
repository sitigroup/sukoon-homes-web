<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('wa_settings')) {
            Schema::table('wa_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('wa_settings', 'off_hours_enabled')) {
                    $table->boolean('off_hours_enabled')->default(false);
                }
                if (! Schema::hasColumn('wa_settings', 'business_hours_start')) {
                    $table->string('business_hours_start', 5)->default('09:00');
                }
                if (! Schema::hasColumn('wa_settings', 'business_hours_end')) {
                    $table->string('business_hours_end', 5)->default('18:00');
                }
                if (! Schema::hasColumn('wa_settings', 'business_timezone')) {
                    $table->string('business_timezone', 64)->default('Asia/Kolkata');
                }
                if (! Schema::hasColumn('wa_settings', 'off_hours_reply_text')) {
                    $table->text('off_hours_reply_text')->nullable();
                }
            });
        }

        if (Schema::hasTable('wa_conversations')) {
            Schema::table('wa_conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('wa_conversations', 'off_hours_auto_reply_at')) {
                    $table->timestamp('off_hours_auto_reply_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wa_settings')) {
            Schema::table('wa_settings', function (Blueprint $table) {
                foreach (['off_hours_enabled', 'business_hours_start', 'business_hours_end', 'business_timezone', 'off_hours_reply_text'] as $col) {
                    if (Schema::hasColumn('wa_settings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('wa_conversations')) {
            Schema::table('wa_conversations', function (Blueprint $table) {
                if (Schema::hasColumn('wa_conversations', 'off_hours_auto_reply_at')) {
                    $table->dropColumn('off_hours_auto_reply_at');
                }
            });
        }
    }
};
