<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('wa_event_map')) {
            Schema::create('wa_event_map', function (Blueprint $table) {
                $table->id();
                $table->string('event_key')->unique();
                $table->foreignId('template_id')->nullable()->constrained('wa_templates')->nullOnDelete();
                $table->boolean('enabled')->default(false);
                $table->string('language', 10)->default('en');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_event_map');
    }
};

