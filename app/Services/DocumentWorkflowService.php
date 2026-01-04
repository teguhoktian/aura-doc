<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Transaction;
use Exception;

class DocumentWorkflowService
{
    /**
     * Rules Transisi Status
     */
    protected array $rules = [
        'in_vault'  => ['borrow', 'notary_send', 'release'],
        'borrowed'  => ['return'],
        'at_notary' => ['notary_return', 'release'],
        'released'  => [],
    ];

    /**
     * Mapping Action → Status Baru
     */
    protected array $nextState = [
        'borrow'        => 'borrowed',
        'return'        => 'in_vault',
        'notary_send'   => 'at_notary',
        'notary_return' => 'in_vault',
        'release'       => 'released',
    ];

    public function apply(Document $document, string $action, array $payload = [])
    {
        $currentStatus = $document->status;

        // --- VALIDASI STATUS ---
        if (! isset($this->rules[$currentStatus])) {
            throw new Exception("Status saat ini tidak dikenali: {$currentStatus}");
        }

        if (! in_array($action, $this->rules[$currentStatus])) {
            throw new Exception("Transisi {$currentStatus} → {$action} tidak diizinkan");
        }

        // --- PROSES ACTION ---
        switch ($action) {

            // -----------------------
            // BORROW INTERNAL
            // -----------------------
            case 'borrow':
                $document->update([
                    'status'        => 'borrowed',
                    'borrower_name' => $payload['borrower_name'] ?? $document->borrower_name,
                    'due_date'      => $payload['due_date'] ?? null,
                    'reason'        => $payload['reason'] ?? null,
                    'borrowed_at'   => now(),
                ]);
                break;

            // -----------------------
            // RETURN TO VAULT
            // -----------------------
            case 'return':
                // update transaksi terakhir yang masih terbuka
                if ($transaction = $document->latestOpenTransaction) {
                    $transaction->update([
                        'returned_at' => isset($payload['returned_at'])
                            ? \Carbon\Carbon::parse($payload['returned_at'])
                            : now(),
                    ]);
                }

                // update status dokumen
                $document->update([
                    'status'     => 'in_vault',
                    'storage_id' => $payload['storage_id'] ?? $document->storage_id,
                ]);

                // upload file receipt jika ada
                if (!empty($payload['receipt'])) {
                    $document->addMedia($payload['receipt'])
                        ->toMediaCollection('borrow_return_receipts');
                }

                break;

            // -----------------------
            // SEND TO NOTARY
            // -----------------------
            case 'notary_send':
                $document->update([
                    'status'             => 'at_notary',
                    'notary_id'          => $payload['notary_id'] ?? null,
                    'sent_to_notary_at'  => now(),
                    'expected_return_at' => $payload['expected_return_at'] ?? null,
                ]);
                break;

            // -----------------------
            // RETURN FROM NOTARY
            // -----------------------
            case 'notary_return':
                $document->update([
                    'status'     => 'in_vault',
                    'returned_at' => now(),
                    'storage_id' => $payload['storage_id'] ?? $document->storage_id,
                ]);
                break;

            // -----------------------
            // RELEASE (PELUNASAN)
            // -----------------------
            case 'release':
                $document->update([
                    'status' => 'released',
                ]);
                break;

            default:
                // jika action tidak ada, tidak ada perubahan
                break;
        }

        // --- CATAT TRANSAKSI ---
        return Transaction::create([
            'document_id'      => $document->id,
            'user_id'          => auth()->id(),
            'type'             => $action,
            'transaction_date' => now(),
            ...$payload
        ]);
    }
}
