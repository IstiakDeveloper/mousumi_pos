<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->date('date')->nullable()->after('type');
        });

        // Set existing records' date to their created_at date
        DB::statement('UPDATE product_stocks SET date = DATE(created_at) WHERE date IS NULL');

        // Make the column NOT NULL after setting values
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
