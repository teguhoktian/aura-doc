<?php

namespace App\Filament\Resources\DocumentReleaseResource\Pages;

use App\Filament\Resources\DocumentReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentReleases extends ListRecords
{
    protected static string $resource = DocumentReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
