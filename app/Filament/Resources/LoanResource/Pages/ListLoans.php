<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
            \Filament\Actions\Action::make('import_data')
                ->label('Upload YBT-ALL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Pilih File CSV/XLSX')
                        ->required()
                        ->disk('local')
                ])
                ->action(function (array $data) {
                    $file = $data['file'];

                    // Proses dijalankan di background (Antrean)
                    \Maatwebsite\Excel\Facades\Excel::queueImport(
                        new \App\Imports\LoanImport,
                        $file,
                        'local'
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Proses Import Dimulai')
                        ->body('Data sedang diproses di latar belakang. Anda akan menerima notifikasi saat selesai.')
                        ->info()
                        ->send();
                })
        ];
    }
}
