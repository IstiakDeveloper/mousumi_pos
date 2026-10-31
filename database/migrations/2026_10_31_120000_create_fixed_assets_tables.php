<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->nullable()->unique();
            $table->string('name');
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fixed_asset_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->string('item_name');
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->unique()->constrained('expenses')->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->transferFixedAssetExpenses();
    }

    public function down(): void
    {
        $expenseIds = DB::table('fixed_asset_items')->whereNotNull('expense_id')->pluck('expense_id');
        DB::table('expenses')->whereIn('id', $expenseIds)->update(['deleted_at' => null]);

        Schema::dropIfExists('fixed_asset_items');
        Schema::dropIfExists('fixed_assets');
    }

    private function transferFixedAssetExpenses(): void
    {
        $category = DB::table('expense_categories')
            ->where('name', 'Fixed Asset')
            ->whereNull('deleted_at')
            ->first();

        if (! $category) {
            return;
        }

        $expenses = DB::table('expenses')
            ->where('expense_category_id', $category->id)
            ->whereNull('deleted_at')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        DB::transaction(function () use ($expenses) {
            $sequence = 1;

            foreach ($expenses as $expense) {
                $bankRow = $this->matchingBankRow($expense);
                $now = now();
                $code = 'FA-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
                $sequence++;

                $assetId = DB::table('fixed_assets')->insertGetId([
                    'asset_code' => $code,
                    'name' => $expense->description,
                    'status' => 'active',
                    'description' => $expense->description,
                    'created_by' => $expense->created_by,
                    'created_at' => $expense->created_at ?? $now,
                    'updated_at' => $expense->updated_at ?? $now,
                ]);

                DB::table('fixed_asset_items')->insert([
                    'fixed_asset_id' => $assetId,
                    'item_name' => $expense->description,
                    'purchase_date' => $expense->date,
                    'purchase_price' => $expense->amount,
                    'current_value' => $expense->amount,
                    'bank_account_id' => $expense->bank_account_id,
                    'bank_transaction_id' => $bankRow->id,
                    'expense_id' => $expense->id,
                    'status' => 'active',
                    'description' => $expense->description,
                    'created_by' => $expense->created_by,
                    'created_at' => $expense->created_at ?? $now,
                    'updated_at' => $expense->updated_at ?? $now,
                ]);

                DB::table('expenses')->where('id', $expense->id)->update([
                    'deleted_at' => $now,
                ]);
            }
        });
    }

    private function matchingBankRow(object $expense): object
    {
        $rows = DB::table('bank_transactions')
            ->whereNull('deleted_at')
            ->where('bank_account_id', $expense->bank_account_id)
            ->where('amount', $expense->amount)
            ->where('transaction_type', 'out')
            ->where('description', 'Expense: '.$expense->description)
            ->orderBy('id')
            ->get();

        if ($rows->count() === 1) {
            return $rows->first();
        }

        $sameDay = $rows->where('date', $expense->date)->values();
        if ($sameDay->count() === 1) {
            return $sameDay->first();
        }

        throw new RuntimeException(
            "Fixed asset expense {$expense->id} ({$expense->description}) matched {$rows->count()} bank rows."
        );
    }
};
