<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Pastikan ini ada
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_code',
        'name',
        'type',
        'parent_id'
    ];

    // Relasi ke Induk (KC)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    // Relasi ke Jaringan di bawahnya (KCP/KK)
    public function children(): HasMany
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    // Relasi ke Loan
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
