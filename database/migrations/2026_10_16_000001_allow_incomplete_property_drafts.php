<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE properties ALTER COLUMN zone_id DROP NOT NULL, ALTER COLUMN description DROP NOT NULL, ALTER COLUMN price_mxn DROP NOT NULL, ALTER COLUMN area_m2 DROP NOT NULL');
        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_publication_complete CHECK (publication_status <> 'publicado' OR (zone_id IS NOT NULL AND description IS NOT NULL AND btrim(description) <> '' AND price_mxn IS NOT NULL AND area_m2 IS NOT NULL))");
    }

    public function down(): void
    {
        // Refuse rollback if incomplete drafts exist; never invent values or delete drafts.
        if (DB::table('properties')->whereNull('zone_id')->orWhereNull('description')->orWhereNull('price_mxn')->orWhereNull('area_m2')->exists()) {
            throw new RuntimeException('Completa los borradores antes de revertir esta migración. No se modificó ningún terreno.');
        }
        DB::statement('ALTER TABLE properties DROP CONSTRAINT properties_publication_complete');
        DB::statement('ALTER TABLE properties ALTER COLUMN zone_id SET NOT NULL, ALTER COLUMN description SET NOT NULL, ALTER COLUMN price_mxn SET NOT NULL, ALTER COLUMN area_m2 SET NOT NULL');
    }
};
