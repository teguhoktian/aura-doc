<?php

namespace App\Filament\Resources\NotaryResource\Pages;

use App\Filament\Resources\NotaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotary extends EditRecord
{
    protected static string $resource = NotaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
