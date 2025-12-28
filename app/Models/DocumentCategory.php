<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class DocumentCategory extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug'];

    protected static function boot()
    {
        parent::boot();
        // Otomatis buat slug saat name diisi
        static::creating(fn($model) => $model->slug = Str::slug($model->name));
    }

    public function document_types()
    {
        return $this->hasMany(DocumentType::class, 'category_id');
    }
}
