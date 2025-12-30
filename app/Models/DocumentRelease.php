<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DocumentRelease extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $fillable = [
        'ba_number',
        'release_date',
        'receiver_name',
        'receiver_id_number',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::created(function ($release) {
            // Kita tidak bisa menggunakan $release->documents di sini karena 
            // relasi belum terhubung saat event created dipicu dari form Filament.
            // Kita akan menangani update status di Level Resource saja agar lebih aman.
        });
    }
}
