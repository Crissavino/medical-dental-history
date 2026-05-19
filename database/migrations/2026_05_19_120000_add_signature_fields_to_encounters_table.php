<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->longText('patient_signature_data')->nullable()->after('status');
            $table->longText('dentist_signature_data')->nullable()->after('patient_signature_data');
            $table->timestamp('patient_signed_at')->nullable()->after('dentist_signature_data');
            $table->foreignId('dentist_signed_by')->nullable()
                  ->constrained('users')->nullOnDelete()->after('patient_signed_at');
            $table->timestamp('dentist_signed_at')->nullable()->after('dentist_signed_by');
            $table->string('signed_ip', 45)->nullable()->after('dentist_signed_at');
            $table->string('signed_hash', 64)->nullable()->after('signed_ip');
            $table->foreignId('rectifies_encounter_id')->nullable()
                  ->constrained('encounters')->nullOnDelete()->after('signed_hash');
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign(['rectifies_encounter_id']);
            $table->dropForeign(['dentist_signed_by']);
            $table->dropColumn([
                'patient_signature_data',
                'dentist_signature_data',
                'patient_signed_at',
                'dentist_signed_by',
                'dentist_signed_at',
                'signed_ip',
                'signed_hash',
                'rectifies_encounter_id',
            ]);
        });
    }
};
