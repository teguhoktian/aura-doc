<?php

namespace App\Filament\Resources\DocumentReleaseResource\Pages;

use App\Filament\Resources\DocumentReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentRelease extends EditRecord
{
    protected static string $resource = DocumentReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
