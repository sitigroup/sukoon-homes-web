<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('tv_order_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('doc_type', 40);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->unsignedBigInteger('uploaded_by_customer_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('tv_orders')->cascadeOnDelete();
            $table->unique(['order_id', 'doc_type']);
        });

        Schema::create('tv_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('provider', 40);
            $table->string('status', 30)->default('queued');
            $table->string('trigger', 40)->default('manual');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('checks_updated')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('tv_orders')->cascadeOnDelete();
        });

        Schema::table('tv_orders', function (Blueprint $table) {
            $table->string('automation_status', 30)->nullable()->after('payment_status');
        });

        $now = now();
        foreach ([
            ['documents_enabled', '1'],
            ['documents_required', '0'],
            ['required_document_types', '["id_front"]'],
            ['automation_enabled', '1'],
            ['automation_provider', 'rules'],
            ['automation_on_paid', '1'],
            ['automation_auto_apply', '1'],
        ] as [$key, $value]) {
            DB::table('tv_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tv_orders', function (Blueprint $table) {
            $table->dropColumn('automation_status');
        });

        Schema::dropIfExists('tv_automation_runs');
        Schema::dropIfExists('tv_order_documents');
        Schema::dropIfExists('tv_settings');
    }
};
