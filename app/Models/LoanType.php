<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Tambahkan ini
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    use HasUuids; // Tambahkan ini agar ID otomatis terisi UUID

    protected $fillable = [
        'code',
        'description',
        'division',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public static function getRecordTitleAttribute(): string
    {
        return 'description';
    }
}
