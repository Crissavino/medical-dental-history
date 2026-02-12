<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('signature_data')->nullable()->after('role');
        });

        Schema::table('anamnesis_versions', function (Blueprint $table) {
            $table->longText('dentist_signature_data')->nullable()->after('signature_data');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete()->after('dentist_signature_data');
            $table->timestamp('signed_at')->nullable()->after('signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('anamnesis_versions', function (Blueprint $table) {
            $table->dropForeign(['signed_by']);
            $table->dropColumn(['dentist_signature_data', 'signed_by', 'signed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_data');
        });
    }
};
