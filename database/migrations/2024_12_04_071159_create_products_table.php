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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->nullable()->constrained();
            $table->foreignId('unit_id')->constrained();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2);
            $table->integer('alert_quantity')->default(0);
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
