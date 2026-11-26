<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regionTags = [
            ['name' => 'Bandar Lampung', 'slug' => 'bandar-lampung'],
            ['name' => 'Lampung Barat', 'slug' => 'lampung-barat'],
            ['name' => 'Lampung Selatan', 'slug' => 'lampung-selatan'],
            ['name' => 'Lampung Tengah', 'slug' => 'lampung-tengah'],
            ['name' => 'Lampung Timur', 'slug' => 'lampung-timur'],
            ['name' => 'Lampung Utara', 'slug' => 'lampung-utara'],
            ['name' => 'Mesuji', 'slug' => 'mesuji'],
            ['name' => 'Metro', 'slug' => 'metro'],
            ['name' => 'Pesawaran', 'slug' => 'pesawaran'],
            ['name' => 'Pesisir Barat', 'slug' => 'pesisir-barat'],
            ['name' => 'Pringsewu', 'slug' => 'pringsewu'],
            ['name' => 'Tanggamus', 'slug' => 'tanggamus'],
            ['name' => 'Tulang Bawang', 'slug' => 'tulang-bawang'],
            ['name' => 'Tulang Bawang Barat', 'slug' => 'tulang-bawang-barat'],
            ['name' => 'Way Kanan', 'slug' => 'way-kanan'],
        ];

        foreach ($regionTags as $regionTag) {
            Tag::firstOrCreate(
                ['slug' => $regionTag['slug']],
                [
                    'name' => $regionTag['name'],
                    'type' => 'region',
                ],
            );
        }
    }
}
