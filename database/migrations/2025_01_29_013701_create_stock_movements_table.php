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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('reference_type'); // sale, purchase, adjustment
            $table->unsignedBigInteger('reference_id');
            $table->decimal('quantity', 15, 6);
            $table->decimal('before_quantity', 15, 6);
            $table->decimal('after_quantity', 15, 6);
            $table->string('type'); // in, out
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
