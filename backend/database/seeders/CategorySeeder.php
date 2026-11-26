<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'News',
                'slug' => 'news',
                'sort_order' => 0,
                'children' => [
                    ['name' => 'Ekonomi', 'slug' => 'ekonomi', 'sort_order' => 1],
                    ['name' => 'Berita Foto', 'slug' => 'foto', 'sort_order' => 2],
                    ['name' => 'Pemerintahan', 'slug' => 'pemerintahan', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Peristiwa',
                'slug' => 'peristiwa',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Bencana Alam', 'slug' => 'bencana-alam', 'sort_order' => 5],
                    ['name' => 'Kebakaran', 'slug' => 'kebakaran', 'sort_order' => 6],
                    ['name' => 'Kecelakaan', 'slug' => 'kecelakaan', 'sort_order' => 7],
                ],
            ],
            [
                'name' => 'Kriminal',
                'slug' => 'kriminal',
                'sort_order' => 8,
                'children' => [
                    ['name' => 'Narkoba', 'slug' => 'narkoba', 'sort_order' => 9],
                    ['name' => 'Pembunuhan', 'slug' => 'pembunuhan', 'sort_order' => 10],
                    ['name' => 'Penangkapan', 'slug' => 'penangkapan', 'sort_order' => 11],
                    ['name' => 'Pencurian', 'slug' => 'pencurian', 'sort_order' => 12],
                    ['name' => 'Penganiayaan', 'slug' => 'penganiayaan', 'sort_order' => 13],
                    ['name' => 'Penipuan', 'slug' => 'penipuan', 'sort_order' => 14],
                ],
            ],
            [
                'name' => 'Cerita Opini',
                'slug' => 'cerita-opini',
                'sort_order' => 15,
                'children' => [
                    ['name' => 'Feature', 'slug' => 'feature', 'sort_order' => 16],
                    ['name' => 'Opini', 'slug' => 'opini', 'sort_order' => 17],
                ],
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'sort_order' => 18,
                'children' => [
                    ['name' => 'Milenial', 'slug' => 'milenial', 'sort_order' => 19],
                    ['name' => 'Olahraga', 'slug' => 'olahraga', 'sort_order' => 20],
                    ['name' => 'Tips Lifestyle', 'slug' => 'tips-lifestyle', 'sort_order' => 21],
                ],
            ],
            [
                'name' => 'Wisata & Kuliner',
                'slug' => 'wisata-kuliner',
                'sort_order' => 22,
                'children' => [
                    ['name' => 'Hotel dan Penginapan', 'slug' => 'hotel-dan-penginapan', 'sort_order' => 23],
                    ['name' => 'Rekomendasi Tempat', 'slug' => 'rekomendasi-tempat', 'sort_order' => 24],
                    ['name' => 'Wisata Alam', 'slug' => 'wisata-alam', 'sort_order' => 25],
                    ['name' => 'Wisata Budaya', 'slug' => 'wisata-budaya', 'sort_order' => 26],
                ],
            ],
        ];

        foreach ($groups as $group) {
            $parent = Category::firstOrCreate(
                ['slug' => $group['slug']],
                [
                    'name' => $group['name'],
                    'sort_order' => $group['sort_order'],
                ],
            );

            foreach ($group['children'] as $child) {
                Category::firstOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'parent_id' => $parent->id,
                        'name' => $child['name'],
                        'sort_order' => $child['sort_order'],
                    ],
                );
            }
        }
    }
}
