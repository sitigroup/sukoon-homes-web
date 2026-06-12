<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('wa_canned_replies')) {
            return;
        }

        Schema::create('wa_canned_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('body_en');
            $table->text('body_hi')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        DB::table('wa_canned_replies')->insert([
            [
                'title' => 'Acknowledge receipt',
                'body_en' => 'Thank you for contacting Sukoon Homes. We have received your message and will respond shortly.',
                'body_hi' => 'सुकून होम्स से संपर्क करने के लिए धन्यवाद। हमें आपका संदेश मिल गया है और हम जल्द उत्तर देंगे।',
                'sort_order' => 10,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Request more details',
                'body_en' => 'Could you please share a few more details so we can assist you better?',
                'body_hi' => 'कृपया कुछ और विवरण साझा करें ताकि हम आपकी बेहतर सहायता कर सकें।',
                'sort_order' => 20,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_canned_replies');
    }
};
