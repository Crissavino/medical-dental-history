<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraction_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('consent_text');
            $table->string('language', 2);
            $table->longText('patient_signature_data');
            $table->timestamp('signed_at');
            $table->string('signed_ip', 45);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraction_consents');
    }
};
