<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plate_id',
        'score',
        'label',
        'warning_message',
        'conflicting_tags',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'conflicting_tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plate(): BelongsTo
    {
        return $this->belongsTo(Plate::class);
    }
}
