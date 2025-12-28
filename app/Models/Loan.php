<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasUuids;

    protected $fillable = [
        'loan_number',
        'debtor_name',
        'plafond',
        'disbursement_date',
        'status'
    ];

    protected $with = ['loan_type'];

    // Menangani format tanggal agar bisa dibaca Filament
    protected $casts = [
        'disbursement_date' => 'date',
        'plafond' => 'decimal:2',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function loan_type()
    {
        return $this->belongsTo(LoanType::class);
    }
}
