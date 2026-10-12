<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('type')->nullable();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->index(['property_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_documents');
    }
};
