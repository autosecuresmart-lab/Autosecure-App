<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueIds;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gives a model a `uuid` public identifier and makes that uuid the route key.
 *
 * AUTOSECURE rule: every API/admin route resolves records by uuid, never by the
 * auto-increment id. The numeric id stays internal (foreign keys, joins).
 *
 * The uuid is assigned by Laravel's HasUniqueIds mechanism, which runs inside
 * performInsert() *before* the `creating` event. That matters because seeders
 * that use WithoutModelEvents (DatabaseSeeder) mute model events — an
 * event-based hook would silently leave uuid empty there.
 *
 * @mixin Model
 */
trait HasUuid
{
    use HasUniqueIds;

    /**
     * Initialize the trait.
     */
    public function initializeHasUuid(): void
    {
        $this->usesUniqueIds = true;
    }

    /**
     * The `uuid` column is the unique identifier surface.
     *
     * @return array<int, string>
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }

    /**
     * Generate a new uuid.
     */
    public function newUniqueId()
    {
        return (string) Str::uuid();
    }

    /**
     * Resolve route model binding and `findOrFail` style lookups by uuid.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Scope a query to a specific uuid.
     */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where($this->getTable().'.uuid', $uuid);
    }
}
