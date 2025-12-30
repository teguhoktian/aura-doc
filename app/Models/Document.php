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
    }

    // app/Models/Document.php

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }


    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function document_type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
