<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('available_quantity', 15, 6)->default(0)->after('quantity');
            $table->string('type')->default('purchase')->after('available_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropColumn('available_quantity');
            $table->dropColumn('type');
        });
    }
};
