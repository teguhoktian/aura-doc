<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $fillable = [
        'loan_id',
        'document_type_id', // Pastikan ini ada
        'document_number',
        'status',
        'storage_id',
        'notary_id',
        'legal_metadata',
        'sent_to_notary_at',
        'expected_return_at',
        'expiry_date',
        'document_release_id',
    ];

    // Cast JSON agar otomatis menjadi array di Laravel
    protected $casts = [
        'sent_to_notary_at' => 'date',
        'expected_return_at' => 'date',
        'expiry_date' => 'date',
        'legal_metadata' => 'array',
    ];

    public static function getStatuses(): array
    {
        return [
            'in_vault' => 'Tersimpan di Vault',
            'at_notary' => 'Di Notaris', // Pastikan ini ada
            'borrowed' => 'Dipinjam Internal',
            'released' => 'Diserahkan (Lunas)',
        ];
    }

    public function notary(): BelongsTo
    {
        return $this->belongsTo(Notary::class, 'notary_id');
    }

    // Relasi ke tabel Loan
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    // Registrasi koleksi media untuk Spatie
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document_scans')
            ->useDisk('private') // Pastikan disk 'private' ada di config/filesystems.php
            ->singleFile();

        $this->addMediaCollection('notary_receipts')
            ->useDisk('private');
    }

    // app/Models/Document.php

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }


    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Helper untuk mengambil transaksi yang masih menggantung (belum kembali)
    public function latestOpenTransaction()
    {
        return $this->hasOne(Transaction::class)->whereNull('returned_at')->latest();
    }

    public function document_type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function document_release()
    {
        return $this->belongsTo(DocumentRelease::class, 'document_release_id');
    }

    public static function getLimitedStatuses(array $allowed): array
    {
        return array_intersect_key(self::getStatuses(), array_flip($allowed));
    }
}
