<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorVerification extends BaseModel
{
    public const STAGE_APPLICATION = 'application';

    public const STAGE_IDENTITY = 'identity';

    public const STAGE_BUSINESS = 'business';

    public const STAGE_LOCATION = 'location';

    public const STAGE_PAYOUT = 'payout';

    public const STAGE_APPROVAL = 'approval';

    public const STAGE_MONITORING = 'monitoring';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PASSED = 'passed';

    public const STATUS_NEEDS_UPDATE = 'needs_update';

    public const STATUS_REJECTED = 'rejected';

    /** Ordered pipeline, used by the admin portal to render progress. */
    public const STAGES = [
        self::STAGE_APPLICATION,
        self::STAGE_IDENTITY,
        self::STAGE_BUSINESS,
        self::STAGE_LOCATION,
        self::STAGE_PAYOUT,
        self::STAGE_APPROVAL,
        self::STAGE_MONITORING,
    ];

    protected $fillable = [
        'vendor_id',
        'stage',
        'status',
        'reviewer_admin_id',
        'notes',
        'documents',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewer_admin_id');
    }
}
