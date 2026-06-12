<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('wa_settings')) {
            Schema::create('wa_settings', function (Blueprint $table) {
                $table->id();
                $table->string('meta_app_id')->nullable();
                $table->string('waba_id')->nullable();
                $table->string('phone_number_id')->nullable();
                $table->text('access_token')->nullable();
                $table->string('verify_token')->nullable();
                $table->text('app_secret')->nullable();
                $table->enum('environment_mode', ['test_number', 'sandbox_waba', 'production_waba'])->default('test_number');
                $table->boolean('webhook_verified')->default(false);
                $table->timestamp('last_tested_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_templates')) {
            Schema::create('wa_templates', function (Blueprint $table) {
                $table->id();
                $table->string('meta_template_name');
                $table->string('language', 10)->default('en');
                $table->string('category')->nullable();
                $table->string('internal_key')->nullable();
                $table->string('status')->nullable();
                $table->json('components_json')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
                $table->unique(['meta_template_name', 'language']);
            });
        }

        if (! Schema::hasTable('wa_contacts')) {
            Schema::create('wa_contacts', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30)->unique();
                $table->enum('customer_type', ['user', 'agent', 'owner', 'tenant', 'vendor'])->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->boolean('opted_in')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_conversations')) {
            Schema::create('wa_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contact_id')->constrained('wa_contacts')->cascadeOnDelete();
                $table->unsignedBigInteger('assigned_agent_id')->nullable();
                $table->enum('status', ['open', 'pending', 'resolved'])->default('open');
                $table->timestamp('last_customer_message_at')->nullable();
                $table->timestamp('conversation_window_expires_at')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_messages')) {
            Schema::create('wa_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('wa_conversations')->cascadeOnDelete();
                $table->foreignId('contact_id')->constrained('wa_contacts')->cascadeOnDelete();
                $table->string('wamid')->nullable()->unique();
                $table->enum('direction', ['in', 'out']);
                $table->string('type')->nullable();
                $table->longText('body')->nullable();
                $table->string('media_url')->nullable();
                $table->string('template_key')->nullable();
                $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
                $table->json('error_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_message_status_log')) {
            Schema::create('wa_message_status_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->nullable()->constrained('wa_messages')->nullOnDelete();
                $table->string('wamid')->nullable();
                $table->string('status')->nullable();
                $table->json('payload_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_audit_log')) {
            Schema::create('wa_audit_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('event');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_audit_log');
        Schema::dropIfExists('wa_message_status_log');
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_conversations');
        Schema::dropIfExists('wa_contacts');
        Schema::dropIfExists('wa_templates');
        Schema::dropIfExists('wa_settings');
    }
};

