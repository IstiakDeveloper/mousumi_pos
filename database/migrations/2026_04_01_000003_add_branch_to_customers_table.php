<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('branch_code', 10)->nullable()->after('address');
            $table->string('branch_name')->nullable()->after('branch_code');
            $table->index(['branch_code']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['branch_code']);
            $table->dropColumn(['branch_code', 'branch_name']);
        });
    }
};

