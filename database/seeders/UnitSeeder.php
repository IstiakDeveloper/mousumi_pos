<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'short_name' => 'pcs', 'status' => true],
            ['name' => 'Set', 'short_name' => 'set', 'status' => true],
            ['name' => 'Pack', 'short_name' => 'pack', 'status' => true],
            ['name' => 'Ream', 'short_name' => 'ream', 'status' => true],
            ['name' => 'Box', 'short_name' => 'box', 'status' => true],
            ['name' => 'Roll', 'short_name' => 'roll', 'status' => true],
            ['name' => 'Dozen', 'short_name' => 'dozen', 'status' => true],
            ['name' => 'Carton', 'short_name' => 'carton', 'status' => true],
            ['name' => 'Bundle', 'short_name' => 'bundle', 'status' => true],
            ['name' => 'Packet', 'short_name' => 'pkt', 'status' => true],
            ['name' => 'Pad', 'short_name' => 'pad', 'status' => true],
            ['name' => 'Tube', 'short_name' => 'tube', 'status' => true],
            ['name' => 'Bottle', 'short_name' => 'bottle', 'status' => true],
        ];

        DB::table('units')->insert($units);

    }
}
