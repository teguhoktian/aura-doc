<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Storage extends Model
{
    use HasUuids;

    protected $fillable = ['parent_id', 'name', 'level', 'code', 'description'];

    protected $appends = ['full_path'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Storage::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Storage::class, 'parent_id');
    }

    // Relasi ke dokumen yang ada di lokasi ini
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'storage_id');
    }

    public function getFullPathAttribute(): string
    {
        // Jika punya induk, ambil nama induk + nama sekarang
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . $this->name;
        }

        return $this->name;
    }
}
