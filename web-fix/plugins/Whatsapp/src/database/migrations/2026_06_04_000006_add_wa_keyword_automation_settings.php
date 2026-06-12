<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_settings')) {
            Schema::table('wa_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('wa_settings', 'keyword_automation_enabled')) {
                    $table->boolean('keyword_automation_enabled')->default(false);
                }
                if (! Schema::hasColumn('wa_settings', 'keyword_reply_rent')) {
                    $table->text('keyword_reply_rent')->nullable();
                }
                if (! Schema::hasColumn('wa_settings', 'keyword_reply_repair')) {
                    $table->text('keyword_reply_repair')->nullable();
                }
                if (! Schema::hasColumn('wa_settings', 'keyword_reply_agreement')) {
                    $table->text('keyword_reply_agreement')->nullable();
                }
            });
        }

        if (Schema::hasTable('wa_conversations')) {
            Schema::table('wa_conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('wa_conversations', 'keyword_auto_reply_at')) {
                    $table->timestamp('keyword_auto_reply_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wa_settings')) {
            Schema::table('wa_settings', function (Blueprint $table) {
                foreach (['keyword_automation_enabled', 'keyword_reply_rent', 'keyword_reply_repair', 'keyword_reply_agreement'] as $col) {
                    if (Schema::hasColumn('wa_settings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('wa_conversations')) {
            Schema::table('wa_conversations', function (Blueprint $table) {
                if (Schema::hasColumn('wa_conversations', 'keyword_auto_reply_at')) {
                    $table->dropColumn('keyword_auto_reply_at');
                }
            });
        }
    }
};
