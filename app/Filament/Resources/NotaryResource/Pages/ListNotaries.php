<?php

namespace App\Filament\Resources\NotaryResource\Pages;

use App\Filament\Resources\NotaryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotaries extends ListRecords
{
    protected static string $resource = NotaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
