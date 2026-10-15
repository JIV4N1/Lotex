<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('phone', 16);
            $table->uuid('submission_token')->unique();
            $table->string('status', 30)->default('nuevo')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('appointment_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->index(['property_id', 'phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_requests');
    }
};
