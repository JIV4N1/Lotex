<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 16);
            $table->string('email')->nullable();
            $table->text('message');
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('submission_token')->unique();
            $table->string('status')->default('nuevo')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
