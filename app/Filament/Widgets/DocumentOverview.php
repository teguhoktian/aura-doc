<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\Notary;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Dokumen di Vault', Document::where('status', 'in_vault')->count())
                ->description('Berkas tersedia di gudang')
                ->descriptionIcon('heroicon-m-building-library', IconPosition::Before)
                ->color('success'),

            Stat::make('Dokumen di Notaris', Document::where('status', 'at_notary')->count())
                ->description('Sedang proses pengikatan/legalitas')
                ->descriptionIcon('heroicon-m-briefcase', IconPosition::Before)
                ->color('info'),

            Stat::make('Dokumen Dipinjam', Document::where('status', 'borrowed')->count())
                ->description('Sedang dipinjam staf internal')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
                ->color('warning'),

            Stat::make('Dokumen Overdue', Document::where('status', 'at_notary')
                ->where('expected_return_at', '<', now())->count())
                ->description('Melewati estimasi kembali')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color('danger'),
        ];
    }
}
