<?php

namespace App\Filament\Resources\DocumentReleaseResource\Pages;

use App\Filament\Resources\DocumentReleaseResource;
use App\Models\Document;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDocumentRelease extends CreateRecord
{
    protected static string $resource = DocumentReleaseResource::class;

    /**
     * TAHAP 1: Memperbaiki Error Not Null Violation
     * Mengisi user_id secara otomatis sebelum data di-insert ke database.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    /**
     * TAHAP 2: Logika Update Status Dokumen
     * Dijalankan setelah record DocumentRelease berhasil dibuat.
     */
    protected function afterCreate(): void
    {
        // Mengambil data mentah dari form (termasuk document_ids yang tipenya dehydrated(false))
        $data = $this->form->getRawState();
        $record = $this->record;

        if (isset($data['document_ids']) && is_array($data['document_ids'])) {
            // Gunakan Database Transaction agar jika satu gagal, semua dibatalkan
            DB::transaction(function () use ($data, $record) {
                foreach ($data['document_ids'] as $docId) {
                    $document = Document::find($docId);

                    if ($document) {
                        // 1. Hubungkan dokumen ke ID Berita Acara ini dan ubah statusnya
                        $document->update([
                            'document_release_id' => $record->id,
                            'status' => 'released',
                            'storage_id' => null, // Kosongkan lokasi fisik karena sudah keluar dari vault
                        ]);

                        // 2. Catat riwayat di tabel transactions
                        $document->transactions()->create([
                            'user_id' => auth()->id(),
                            'borrower_name' => $record->receiver_name,
                            'type' => 'release',
                            'transaction_date' => $record->release_date,
                            'reason' => 'Penyerahan Dokumen melalui Berita Acara No: ' . $record->ba_number,
                        ]);
                    }
                }
            });
        }
    }

    /**
     * Redirect ke halaman index setelah berhasil simpan
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
