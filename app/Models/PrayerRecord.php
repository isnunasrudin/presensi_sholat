<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerRecord extends Model
{
    protected $fillable = [
        'user_id',
        'prayer_type',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user that owns the prayer record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
