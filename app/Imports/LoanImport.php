<?php

namespace App\Imports;

use App\Models\Loan;
use App\Models\Branch;
use App\Models\LoanType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Contracts\Queue\ShouldQueue; // Agar berjalan di background
use Carbon\Carbon;

class LoanImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithBatchInserts, ShouldQueue
{
    public function uniqueBy()
    {
        return 'loan_number';
    }

    public function model(array $row)
    {
        $branch = Branch::where('branch_code', $row['kantor_cabang'])->first();
        $loanType = LoanType::where('code', $row['jenis_kredit'])->first();

        if (!$loanType) {
            // Log ke file atau database untuk audit jika master data tidak ada
            \Log::warning("Import Skip: Loan Type {$row['jenis_kredit']} tidak ditemukan.");
            return null;
        }

        return new Loan([
            'loan_number'       => $row['nomor_kontrak'],
            'debtor_name'       => $row['cust_full_name'],
            'branch_id'         => $branch?->id,
            'loan_type_id'      => $loanType->id,
            'plafond'           => $row['plafond'],
            'status'            => strtolower($row['status']) == 'aktif' ? Loan::STATUS_ACTIVE : Loan::STATUS_CLOSED,
            'disbursement_date' => Carbon::parse($row['tgl_pencairan']),
        ]);
    }

    public function batchSize(): int
    {
        return 1000; // Mengirim 1000 data sekaligus ke DB (Sangat Cepat)
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
