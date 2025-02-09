<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('extra_income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Add nullable column to extra_incomes
        Schema::table('extra_incomes', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('bank_account_id')
                  ->constrained('extra_income_categories');
        });
    }

    public function down()
    {
        Schema::table('extra_incomes', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('extra_income_categories');
    }
};
