<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unique(['state_id', 'name']);
            $table->timestamps();
        });
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unique(['municipality_id', 'name']);
            $table->timestamps();
        });
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->index()->constrained()->restrictOnDelete();
            $table->string('code', 64)->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->decimal('price_mxn', 16, 2);
            $table->decimal('area_m2', 14, 2);
            $table->decimal('frontage_m', 12, 2)->nullable();
            $table->decimal('depth_m', 12, 2)->nullable();
            $table->text('reference_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->jsonb('services')->default('[]');
            $table->text('access')->nullable();
            $table->text('topography')->nullable();
            $table->text('land_use')->nullable();
            $table->text('documentary_summary')->nullable();
            $table->text('payment_terms')->nullable();
            $table->enum('publication_status', ['borrador', 'publicado'])->default('borrador');
            $table->enum('availability_status', ['disponible', 'apartado', 'vendido'])->default('disponible');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['publication_status', 'availability_status', 'deleted_at'], 'properties_public_availability_index');
            $table->index(['publication_status', 'is_featured', 'deleted_at'], 'properties_public_featured_index');
        });
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_price_nonnegative CHECK (price_mxn >= 0)');
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_area_positive CHECK (area_m2 > 0)');
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_frontage_positive CHECK (frontage_m IS NULL OR frontage_m > 0)');
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_depth_positive CHECK (depth_m IS NULL OR depth_m > 0)');
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_latitude_range CHECK (latitude BETWEEN -90 AND 90)');
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_longitude_range CHECK (longitude BETWEEN -180 AND 180)');
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('path');
            $table->string('alt_text');
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->index(['property_id', 'position', 'id']);
        });
        DB::statement('ALTER TABLE property_images ADD CONSTRAINT property_images_position_nonnegative CHECK (position >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('states');
    }
};
