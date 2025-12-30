<?php

namespace App\Filament\Resources\DocumentReleaseResource\Pages;

use App\Filament\Resources\DocumentReleaseResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf; // <--- PASTIKAN TULISANNYA SEPERTI INI

class ViewDocumentRelease extends ViewRecord
{
    protected static string $resource = DocumentReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_ba')
                ->label('Cetak Berita Acara')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->action(function () {
                    $record = $this->record;

                    // Pastikan relasi diload agar tidak error di blade
                    $record->load(['documents.document_type', 'documents.loan', 'user']);

                    $pdf = Pdf::loadView('pdf.ba-release', [
                        'record' => $record,
                    ]);

                    $filename = preg_replace('/[\/\\\\:*?"<>|]+/', '-', "BA-{$record->ba_number}.pdf");

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
                }),
        ];
    }
}
