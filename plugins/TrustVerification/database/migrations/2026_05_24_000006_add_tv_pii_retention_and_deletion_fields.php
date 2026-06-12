<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tv_order_documents', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('uploaded_by_customer_id');
            $table->string('deleted_by_type', 20)->nullable()->after('deleted_at');
            $table->unsignedBigInteger('deleted_by_id')->nullable()->after('deleted_by_type');
            $table->string('delete_reason', 255)->nullable()->after('deleted_by_id');
        });

        Schema::table('tv_reports', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('uploaded_by');
            $table->string('deleted_by_type', 20)->nullable()->after('deleted_at');
            $table->unsignedBigInteger('deleted_by_id')->nullable()->after('deleted_by_type');
            $table->string('delete_reason', 255)->nullable()->after('deleted_by_id');
        });

        $now = now();
        foreach ([
            ['document_retention_days', '90'],
            ['report_retention_days', '365'],
            ['delete_cancelled_unpaid_after_days', '7'],
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
        Schema::table('tv_reports', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by_type', 'deleted_by_id', 'delete_reason']);
        });

        Schema::table('tv_order_documents', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by_type', 'deleted_by_id', 'delete_reason']);
        });

        DB::table('tv_settings')->whereIn('key', [
            'document_retention_days',
            'report_retention_days',
            'delete_cancelled_unpaid_after_days',
        ])->delete();
    }
};
