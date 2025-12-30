<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Loan extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $fillable = [
        'loan_number',
        'debtor_name',
        'plafond',
        'disbursement_date',
        'status',
        'settled_at',
        'settlement_principal',
        'settlement_interest',
        'settlement_penalty_principal',
        'settlement_penalty_interest',
        'write_off_basis_number',
        'branch_id',      // Tambahkan ini
        'loan_type_id',   // Tambahkan ini
    ];

    protected $with = ['loan_type'];

    // Senior Approach: Gunakan Konstanta
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_WRITE_OFF = 'write_off';

    // Helper untuk label bahasa Indonesia
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_CLOSED => 'Lunas',
            self::STATUS_WRITE_OFF => 'Hapus Buku',
        ];
    }

    // Menangani format tanggal agar bisa dibaca Filament
    protected $casts = [
        'settled_at' => 'date',
        'disbursement_date' => 'date',
        'plafond' => 'decimal:2',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('settlement_documents')
            ->useDisk('private')
            ->singleFile();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function loan_type()
    {
        return $this->belongsTo(LoanType::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // Pastikan foreign key di tabel loans adalah branch_id
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
