<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasUuids;

    protected $fillable = [
        'document_id',
        'user_id',
        'borrower_name',
        'type',
        'transaction_date',
        'due_date',
        'returned_at',
        'reason'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'due_date' => 'date',
        'returned_at' => 'date',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->useDisk('private');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
