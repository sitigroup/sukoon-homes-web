<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('wa_conversation_tags')) {
            return;
        }

        Schema::create('wa_conversation_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('wa_conversations')->cascadeOnDelete();
            $table->string('tag', 32);
            $table->boolean('auto_applied')->default(false);
            $table->timestamps();
            $table->unique(['conversation_id', 'tag']);
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversation_tags');
    }
};
