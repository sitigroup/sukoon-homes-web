<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tv_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->after('payment_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tv_orders', function (Blueprint $table) {
            $table->dropColumn('payment_transaction_id');
        });
    }
};
