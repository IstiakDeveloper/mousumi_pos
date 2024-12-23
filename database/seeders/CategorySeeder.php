<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Writing Instruments',
                'slug' => Str::slug('Writing Instruments'),
                'parent_id' => null,
                'description' => 'Includes pens, pencils, markers, and highlighters.',
                'image' => null,
                'status' => true,
            ],
            [
                'name' => 'Paper Products',
                'slug' => Str::slug('Paper Products'),
                'parent_id' => null,
                'description' => 'Includes notebooks, sticky notes, and printer paper.',
                'image' => null,
                'status' => true,
            ],
            [
                'name' => 'Art and Craft Supplies',
                'slug' => Str::slug('Art and Craft Supplies'),
                'parent_id' => null,
                'description' => 'Includes paints, brushes, and craft papers.',
                'image' => null,
                'status' => true,
            ],
            [
                'name' => 'Office Supplies',
                'slug' => Str::slug('Office Supplies'),
                'parent_id' => null,
                'description' => 'Includes staplers, files, and punch machines.',
                'image' => null,
                'status' => true,
            ],
            [
                'name' => 'School Supplies',
                'slug' => Str::slug('School Supplies'),
                'parent_id' => null,
                'description' => 'Includes geometry sets, school bags, and erasers.',
                'image' => null,
                'status' => true,
            ],
        ];

        DB::table('categories')->insert($categories);
    }
}
