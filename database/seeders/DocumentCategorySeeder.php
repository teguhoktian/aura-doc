<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // database/seeders/DocumentCategorySeeder.php
    public function run()
    {
        $categories = ['Legalitas', 'Agunan', 'Kepegawaian ASN', 'Pengikatan', 'Kredit'];
        foreach ($categories as $cat) {
            \App\Models\DocumentCategory::create(['name' => $cat]);
        }
    }
}
