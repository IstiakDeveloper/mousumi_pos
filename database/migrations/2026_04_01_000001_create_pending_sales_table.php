<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_sales', function (Blueprint $table) {
            $table->id();

            $table->string('public_order_no')->unique();
            $table->foreignId('customer_id')->nullable()->constrained();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('note')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();

            // Link to final Sale when approved
            $table->foreignId('sale_id')->nullable()->constrained('sales');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_sales');
    }
};

