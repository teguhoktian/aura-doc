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

        if (! isset($this->rules[$currentStatus])) {
            throw new Exception("Status saat ini tidak dikenali: {$currentStatus}");
        }

        if (! in_array($action, $this->rules[$currentStatus])) {
            throw new Exception("Transisi {$currentStatus} → {$action} tidak diizinkan");
        }

        $transactionPayload = [
            'document_id'      => $document->id,
            'user_id'          => auth()->id(),
            'type'             => $action,
            'transaction_date' => now(),
        ];

        switch ($action) {

            case 'borrow':
                $document->update([
                    'status'        => 'borrowed',
                    'borrower_name' => $payload['borrower_name'] ?? null,
                    'due_date'      => $payload['due_date'] ?? null,
                    'reason'        => $payload['reason'] ?? null,
                    'borrowed_at'   => now(),
                ]);

                $transactionPayload = array_merge($transactionPayload, [
                    'borrower_name' => $payload['borrower_name'] ?? null,
                    'due_date'      => $payload['due_date'] ?? null,
                    'reason'        => $payload['reason'] ?? null,
                ]);
                break;


            case 'notary_send':
                $document->update([
                    'status'             => 'at_notary',
                    'notary_id'          => $payload['notary_id'] ?? null,
                    'sent_to_notary_at'  => $payload['sent_to_notary_at'] ?? now(),
                    'expected_return_at' => $payload['expected_return_at'] ?? null,
                ]);

                $transactionPayload = array_merge($transactionPayload, [
                    'borrower_name' => optional(\App\Models\Notary::find($payload['notary_id']))->name,
                    'due_date'      => $payload['expected_return_at'] ?? null,
                    'reason'        => $payload['reason'] ?? null,
                ]);
                break;


            case 'return':

                if ($transaction = $document->latestOpenTransaction) {
                    $transaction->update([
                        'returned_at' => $payload['returned_at'] ?? now(),
                        'storage_id'  => $payload['storage_id'] ?? null,
                    ]);
                }

                $document->update([
                    'status'     => 'in_vault',
                    'storage_id' => $payload['storage_id'] ?? $document->storage_id,
                ]);

                // catat jejak aksi return
                $transactionPayload = array_merge($transactionPayload, [
                    'returned_at' => $payload['returned_at'] ?? now(),
                    'storage_id'  => $payload['storage_id'] ?? null,
                ]);

                break;



            case 'notary_return':

                if ($transaction = $document->latestOpenTransaction) {
                    $transaction->update([
                        'returned_at' => $payload['returned_at'] ?? now(),
                        'storage_id'  => $payload['storage_id'] ?? null,
                    ]);
                }

                $document->update([
                    'status'              => 'in_vault',
                    'storage_id'          => $payload['storage_id'] ?? $document->storage_id,
                    'notary_id'           => null,
                    'sent_to_notary_at'   => null,
                    'expected_return_at'  => null,
                ]);

                // catat jejak aksi notary return
                $transactionPayload = array_merge($transactionPayload, [
                    'returned_at' => $payload['returned_at'] ?? now(),
                    'storage_id'  => $payload['storage_id'] ?? null,
                ]);

                break;
        }

        return Transaction::create($transactionPayload);
    }
}
