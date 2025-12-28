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
        'document_type',
        'document_number',
        'legal_metadata',
        'status',
    ];

    // Cast JSON agar otomatis menjadi array di Laravel
    protected $casts = [
        'legal_metadata' => 'array',
    ];

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
}
