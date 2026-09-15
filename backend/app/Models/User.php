<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * AUTOSECURE customer.
 *
 * Mobile authentication uses Sanctum personal access tokens issued from
 * POST /api/v1/auth/login. Staff accounts live in App\Models\Admin on a
 * completely separate table and guard.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuid, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'locale',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'biometric_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Vehicles shared with this user by another owner.
     */
    public function vehicleGrants(): HasMany
    {
        return $this->hasMany(VehicleAccessGrant::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function fuelRecords(): HasMany
    {
        return $this->hasMany(FuelRecord::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'grace'])
            ->latestOfMany();
    }

    public function coinWallet(): HasOne
    {
        return $this->hasOne(CoinWallet::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'owner_user_id');
    }

    public function pushTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function theftEvents(): HasMany
    {
        return $this->hasMany(TheftEvent::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Devices this customer can reach: their own, plus those bound to vehicles
     * they own or hold an active grant for.
     */
    public function visibleDevices(): HasMany
    {
        return $this->hasMany(Device::class)->visibleTo($this);
    }

    /**
     * Password reset emails deep-link into the mobile app instead of a web route.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
