<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LoanType;
use Illuminate\Support\Facades\File;

class LoanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvData = File::get(base_path('database/data/loan_type_db.csv'));
        $rows = explode("\n", $csvData);

        // Lewati header
        foreach (array_slice($rows, 1) as $row) {
            $data = str_getcsv($row);
            if (count($data) < 3) continue;

            LoanType::create([
                'code' => $data[0],
                'description' => $data[1],
                'division' => $data[2],
            ]);
        }
    }
}
