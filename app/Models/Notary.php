<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notary extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'email', 'phone', 'address', 'is_active'];

    public function documents()
    {
        // Notaris akan berhubungan dengan banyak dokumen yang sedang diproses
        return $this->hasMany(Document::class, 'notary_id');
    }
}
