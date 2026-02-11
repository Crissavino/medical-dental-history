<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->index('encounter_id');
            $table->string('tooth_number', 3)->nullable();
            $table->index('tooth_number');
            $table->string('treatment_code', 20);
            $table->index('treatment_code');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->enum('surface', ['mesial', 'distal', 'buccal', 'lingual', 'occlusal', 'incisal'])->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed'])->default('planned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
