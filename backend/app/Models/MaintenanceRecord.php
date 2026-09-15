<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehicle Care Memory record (proposal section 03).
 */
class MaintenanceRecord extends BaseModel
{
    use SoftDeletes;

    public const CATEGORY_OIL_CHANGE = 'oil_change';

    public const CATEGORY_BRAKE_SERVICE = 'brake_service';

    public const CATEGORY_TYRE_REPLACEMENT = 'tyre_replacement';

    public const CATEGORY_BATTERY = 'battery';

    public const CATEGORY_GENERAL_SERVICE = 'general_service';

    public const CATEGORY_REPAIR = 'repair';

    public const CATEGORY_INSPECTION = 'inspection';

    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_OIL_CHANGE,
        self::CATEGORY_BRAKE_SERVICE,
        self::CATEGORY_TYRE_REPLACEMENT,
        self::CATEGORY_BATTERY,
        self::CATEGORY_GENERAL_SERVICE,
        self::CATEGORY_REPAIR,
        self::CATEGORY_INSPECTION,
        self::CATEGORY_OTHER,
    ];

    public const REMINDER_UPCOMING = 'upcoming';

    public const REMINDER_DUE_SOON = 'due_soon';

    public const REMINDER_DUE = 'due';

    public const REMINDER_OVERDUE = 'overdue';

    public const REMINDER_COMPLETED = 'completed';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'category',
        'title',
        'description',
        'performed_at',
        'odometer_km',
        'vendor_id',
        'workshop_name',
        'cost',
        'currency',
        'details',
        'attachments',
        'notes',
        'next_due_at',
        'next_due_odometer_km',
        'reminder_status',
        'odometer_source',
        'booking_id',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
            'odometer_km' => 'integer',
            'cost' => 'decimal:2',
            'details' => 'array',
            'attachments' => 'array',
            'next_due_at' => 'date',
            'next_due_odometer_km' => 'integer',
            'reminder_notified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
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

    public function reminders(): HasMany
    {
        return $this->hasMany(MaintenanceReminder::class);
    }
}
