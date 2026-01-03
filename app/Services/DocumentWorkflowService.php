<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Transaction;
use Exception;

class DocumentWorkflowService
{
    protected array $rules = [
        'in_vault' => ['borrow', 'notary_send', 'release'],
        'borrowed' => ['return'],
        'at_notary' => ['notary_return', 'release'],
        'released' => [] // final
    ];

    protected array $nextState = [
        'borrow' => 'borrowed',
        'return' => 'in_vault',
        'notary_send' => 'at_notary',
        'notary_return' => 'in_vault',
        'release' => 'released',
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

        // Simpan transaksi
        $transaction = Transaction::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'type' => $action,
            'transaction_date' => now(),
            ...$payload
        ]);

        // Update status
        $document->status = $this->nextState[$action];
        $document->save();

        return $transaction;
    }
}
