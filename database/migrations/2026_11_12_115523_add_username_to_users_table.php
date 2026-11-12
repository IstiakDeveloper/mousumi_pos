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
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }

        // Populate usernames for existing users if any
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $baseUsername = '';
            if (!empty($user->email)) {
                $baseUsername = strtolower(explode('@', $user->email)[0]);
            }
            if (empty($baseUsername)) {
                $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
            }
            if (empty($baseUsername)) {
                $baseUsername = 'user' . $user->id;
            }

            // Ensure unique username
            $candidate = $baseUsername;
            $counter = 1;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = $baseUsername . $counter;
                $counter++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
