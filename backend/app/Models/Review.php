<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'booking_id',
        'rating',
        'title',
        'comment',
        'vendor_reply',
        'vendor_replied_at',
        'is_verified_booking',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_booking' => 'boolean',
            'is_published' => 'boolean',
            'vendor_replied_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
