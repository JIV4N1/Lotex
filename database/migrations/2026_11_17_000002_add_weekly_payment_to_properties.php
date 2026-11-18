<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', fn (Blueprint $table) => $table->decimal('weekly_payment_mxn', 16, 2)->nullable());
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_weekly_payment_nonnegative CHECK (weekly_payment_mxn IS NULL OR weekly_payment_mxn >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE properties DROP CONSTRAINT properties_weekly_payment_nonnegative');
        Schema::table('properties', fn (Blueprint $table) => $table->dropColumn('weekly_payment_mxn'));
    }
};
