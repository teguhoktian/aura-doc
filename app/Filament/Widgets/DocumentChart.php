<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DocumentChart extends ChartWidget
{
    protected static ?string $heading = 'Pertumbuhan Berkas Masuk';

    // Urutan tampil (setelah Statistik Overview)
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Query untuk mengambil jumlah dokumen per bulan di tahun ini
        $data = Document::select(
            DB::raw('count(id) as total'),
            DB::raw("to_char(created_at, 'Mon') as month"), // Format PostgreSQL untuk nama bulan singkat
            DB::raw("extract(month from created_at) as month_num")
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month', 'month_num')
            ->orderBy('month_num')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Dokumen Baru',
                    'data' => $data->pluck('total')->toArray(),
                    'fill' => 'start',
                    'tension' => 0.3, // Membuat garis sedikit melengkung (smooth)
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
