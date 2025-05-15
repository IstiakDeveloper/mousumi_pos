<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft delete to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
