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
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_category_id')->constrained();
                $table->foreignId('bank_account_id')->constrained();
                $table->decimal('amount', 10, 2);
                $table->text('description')->nullable();
                $table->string('reference_no')->nullable();
                $table->date('date');
                $table->string('attachment')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
