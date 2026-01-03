<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Jika saat input baru statusnya langsung 'at_notary'
        if ($record->status === 'at_notary') {
            $record->transactions()->create([
                'user_id' => auth()->id(),
                'type' => 'notary_send',
                'borrower_name' => $record->notary?->name,
                'transaction_date' => $record->sent_to_notary_at ?? now(),
                'due_date' => $record->expected_return_at,
                'reason' => 'Input data awal langsung posisi di Notaris.',
                'notes' => 'File Tanda Terima: ' . $record->initial_notary_receipt, // Menyimpan path file
            ]);
        }
    }
}
