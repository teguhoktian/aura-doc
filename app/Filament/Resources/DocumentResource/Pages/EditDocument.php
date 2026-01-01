<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    // Hook ini jalan tepat setelah data berhasil disimpan ke database
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Kita cek apakah status memang berubah dalam sesi simpan ini
        // Laravel merekam perubahan ini di properti wasChanged
        if ($record->wasChanged('status')) {
            $state = $record->status;

            $notaryName = null;
            if ($state === 'at_notary') {
                $notaryName = $record->notary?->name ?? 'Notaris Belum Dipilih';
            }

            // Simpan transaksi
            $record->transactions()->create([
                'user_id' => auth()->id(),
                'type' => $state === 'at_notary' ? 'notary_send' : 'return',
                'transaction_date' => now(),
                'borrower_name' => $notaryName ?? 'SISTEM',
                'reason' => "Status diperbarui via Form Edit menjadi: " . (Document::getStatuses()[$state] ?? $state),
                'due_date' => $record->expected_return_at,
            ]);

            // Dispatch refresh agar Relation Manager di bawah langsung terupdate
            $this->dispatch('refreshTransactions');
        }
    }
}
