<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('development_status')->default('consultas');
            $table->decimal('area_max_m2', 14, 2)->nullable();
            $table->decimal('down_payment_mxn', 16, 2)->nullable();
            $table->decimal('monthly_payment_mxn', 16, 2)->nullable();
        });
        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_development_status CHECK (development_status IN ('consultas','pausada','finalizada'))");
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_general_amounts CHECK ((area_max_m2 IS NULL OR (area_max_m2 > 0 AND (area_m2 IS NULL OR area_max_m2 >= area_m2))) AND (down_payment_mxn IS NULL OR down_payment_mxn >= 0) AND (monthly_payment_mxn IS NULL OR monthly_payment_mxn >= 0))');
        // Seed general reference amounts once. Future child edits do not change them.
        DB::statement('UPDATE properties p SET price_mxn = COALESCE(p.price_mxn, summary.price), area_m2 = COALESCE(p.area_m2, summary.area), area_max_m2 = GREATEST(p.area_m2, summary.max_area) FROM (SELECT lotification_id, MIN(price_mxn) AS price, MIN(area_m2) AS area, MAX(area_m2) AS max_area FROM properties WHERE lotification_id IS NOT NULL AND deleted_at IS NULL GROUP BY lotification_id) summary WHERE p.id = summary.lotification_id AND p.is_lotification');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE properties DROP CONSTRAINT properties_general_amounts, DROP CONSTRAINT properties_development_status');
        Schema::table('properties', fn (Blueprint $table) => $table->dropColumn(['development_status', 'area_max_m2', 'down_payment_mxn', 'monthly_payment_mxn']));
    }
};
