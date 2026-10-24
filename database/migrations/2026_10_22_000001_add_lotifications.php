<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $t) {
            $t->boolean('is_lotification')->default(false);
            $t->foreignId('lotification_id')->nullable()->constrained('properties')->restrictOnDelete();
            $t->string('block', 40)->nullable();
            $t->string('lot_number', 40)->nullable();
            $t->unique(['lotification_id', 'block', 'lot_number']);
        });
        DB::statement('ALTER TABLE properties DROP CONSTRAINT properties_publication_complete');
        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_publication_complete CHECK (publication_status <> 'publicado' OR ((lotification_id IS NOT NULL OR (zone_id IS NOT NULL AND description IS NOT NULL AND btrim(description) <> '')) AND (is_lotification OR (price_mxn IS NOT NULL AND area_m2 IS NOT NULL))))");
        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_lot_identity CHECK ((lotification_id IS NULL AND block IS NULL AND lot_number IS NULL) OR (NOT is_lotification AND lotification_id <> id AND block IS NOT NULL AND btrim(block) <> '' AND lot_number IS NOT NULL AND btrim(lot_number) <> ''))");
    }

    public function down(): void
    {
        throw new RuntimeException('Reversión manual requerida: conserva las lotificaciones y sus lotes.');
    }
};
