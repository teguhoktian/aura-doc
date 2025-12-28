<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Dokumen', Document::count())
                ->description('Semua dokumen terdaftar')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info'),
            Stat::make('Sedang Dipinjam', Document::where('status', 'borrowed')->count())
                ->description('Dokumen di luar gudang')
                ->color('warning'),
            Stat::make('Overdue (Terlambat)', Transaction::where('type', 'borrow')
                ->whereNull('returned_at')
                ->where('due_date', '<', now())
                ->count())
                ->description('Segera hubungi peminjam')
                ->color('danger'),
        ];
    }
}
