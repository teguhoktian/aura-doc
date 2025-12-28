<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'category',
        'is_mandatory',
        'has_expiry',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
