<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'SK CPNS', 'category' => 'Kepegawaian ASN', 'has_expiry' => false],
            ['name' => 'SK PNS', 'category' => 'Kepegawaian ASN', 'has_expiry' => false],
            ['name' => 'SK Golongan', 'category' => 'Kepegawaian ASN', 'has_expiry' => true],
            ['name' => 'Jaminan Hutang', 'category' => 'Pengikatan', 'has_expiry' => false],
            ['name' => 'Fidusia', 'category' => 'Pengikatan', 'has_expiry' => true],
            ['name' => 'Sertifikat Sertifikasi Guru', 'category' => 'Kepegawaian Guru', 'has_expiry' => false],
        ];

        foreach ($types as $type) {
            \App\Models\DocumentType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
