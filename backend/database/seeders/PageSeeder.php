<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'Tentang Kami',
                'content' => 'LampungGeh adalah portal berita dan informasi yang menyediakan karya jurnalistik lokal, regional, dan nasional. Kami hadir untuk memberikan ruang bagi para pencari berita untuk mengikuti serta mendukung karya jurnalisme, dengan editorial independence dalam setiap proses penyiapan konten.',
            ],
        );

        Page::firstOrCreate(
            ['slug' => 'pedoman-media'],
            [
                'title' => 'Pedoman Media Siber',
                'content' => 'Pedoman Media Siber LampungGeh adalah acuan internal penyiapan, penyajian, dan interaksi berita melalui media siber. Dokumen ini mengatur etika jurnalistik, batasan konten, serta tata cara publikasi yang wajib dipatuhi oleh seluruh kontributor dan mitra redaksi.',
            ],
        );
    }
}
