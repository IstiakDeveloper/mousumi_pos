<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->index(['product_id', 'type', 'created_at'], 'product_stocks_product_type_created_idx');
            $table->index('created_at', 'product_stocks_created_at_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['deleted_at', 'created_at'], 'sales_deleted_created_idx');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->index(['bank_account_id', 'date'], 'bank_transactions_account_date_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('date', 'expenses_date_idx');
        });

        Schema::table('extra_incomes', function (Blueprint $table) {
            $table->index('date', 'extra_incomes_date_idx');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->index('date', 'funds_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropIndex('product_stocks_product_type_created_idx');
            $table->dropIndex('product_stocks_created_at_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_deleted_created_idx');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex('bank_transactions_account_date_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_date_idx');
        });

        Schema::table('extra_incomes', function (Blueprint $table) {
            $table->dropIndex('extra_incomes_date_idx');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->dropIndex('funds_date_idx');
        });
    }
};
