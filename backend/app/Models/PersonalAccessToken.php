<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Sanctum token extended with a uuid, so signed-in devices follow the same
 * id + uuid rule as every other AUTOSECURE table and can be revoked by uuid.
 *
 * Registered via Sanctum::usePersonalAccessTokenModel() in AppServiceProvider.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuid;
}
