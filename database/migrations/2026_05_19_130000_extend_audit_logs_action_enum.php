<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created','updated','deleted','signed','rectified') NOT NULL");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite stores enums as TEXT with a CHECK constraint. Rebuild the table to widen it.
            Schema::create('audit_logs__new', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->index(['entity_type', 'entity_id']);
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->index('user_id');
                $table->enum('action', ['created', 'updated', 'deleted', 'signed', 'rectified']);
                $table->json('metadata_json')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('created_at');
            });

            DB::statement('INSERT INTO audit_logs__new (id, entity_type, entity_id, user_id, action, metadata_json, ip_address, created_at) SELECT id, entity_type, entity_id, user_id, action, metadata_json, ip_address, created_at FROM audit_logs');
            Schema::drop('audit_logs');
            Schema::rename('audit_logs__new', 'audit_logs');
            return;
        }

        // Fallback: try changing to a string column.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 32)->change();
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created','updated','deleted') NOT NULL");
            return;
        }

        if ($driver === 'sqlite') {
            Schema::create('audit_logs__old', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->index(['entity_type', 'entity_id']);
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->index('user_id');
                $table->enum('action', ['created', 'updated', 'deleted']);
                $table->json('metadata_json')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('created_at');
            });

            DB::statement("INSERT INTO audit_logs__old (id, entity_type, entity_id, user_id, action, metadata_json, ip_address, created_at) SELECT id, entity_type, entity_id, user_id, action, metadata_json, ip_address, created_at FROM audit_logs WHERE action IN ('created','updated','deleted')");
            Schema::drop('audit_logs');
            Schema::rename('audit_logs__old', 'audit_logs');
        }
    }
};
