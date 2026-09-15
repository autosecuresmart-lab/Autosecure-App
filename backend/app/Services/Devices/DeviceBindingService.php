<?php

namespace App\Services\Devices;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Vehicle ↔ device binding.
 *
 * Binding is a platform record: it decides which vehicle a tracker or dashcam
 * belongs to, and therefore who may see its location and video. It deliberately
 * does NOT talk to a provider — the provider's own pairing/binding call is part
 * of the pending integration.
 *
 * Real manual pairing (QR code / barcode / pairing code) also belongs with the
 * provider, because only the provider can prove physical possession of the
 * device. Until then a device can only be bound by the customer AUTOSECURE
 * reserved it for (see `devices.user_id`), which is how a shipped order is
 * provisioned.
 */
class DeviceBindingService
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Attach a device to a vehicle.
     *
     * @throws \RuntimeException when the device is already bound elsewhere.
     */
    public function bind(Device $device, Vehicle $vehicle, ?User $actor = null): Device
    {
        if ($device->vehicle_id !== null && (int) $device->vehicle_id !== (int) $vehicle->getKey()) {
            throw new \RuntimeException('This device is already linked to another vehicle.');
        }

        return DB::transaction(function () use ($device, $vehicle, $actor) {
            $before = $device->only(['vehicle_id', 'user_id', 'status']);

            $device->fill([
                'vehicle_id' => $vehicle->getKey(),
                // The device follows the vehicle owner, so access control has a
                // single source of truth.
                'user_id' => $vehicle->user_id,
                'status' => Device::STATUS_ACTIVE,
            ]);

            $device->bound_at = $device->bound_at ?? now();
            $device->unbound_at = null;
            $device->save();

            $this->audit->log(
                action: 'devices.bound',
                auditable: $device,
                actor: $actor,
                changes: [
                    'before' => $before,
                    'after' => $device->only(['vehicle_id', 'user_id', 'status']),
                ],
                context: ['vehicle_uuid' => $vehicle->uuid],
                severity: 'notice',
            );

            return $device->fresh();
        });
    }

    /**
     * Detach a device from its vehicle.
     *
     * The provider call that releases the device on the vendor platform cannot
     * happen yet, so it is recorded as outstanding on the device rather than
     * silently forgotten. Phase 3/4 completes it once the provider is wired.
     */
    public function unbind(Device $device, ?User $actor = null, ?string $reason = null): Device
    {
        if ($device->vehicle_id === null && $device->status === Device::STATUS_UNBOUND) {
            return $device;
        }

        return DB::transaction(function () use ($device, $actor, $reason) {
            $before = $device->only(['vehicle_id', 'user_id', 'status']);

            $metadata = $device->metadata ?? [];
            $metadata['provider_unbind_pending'] = true;
            $metadata['provider_unbind_requested_at'] = now()->toIso8601String();

            $device->fill([
                'vehicle_id' => null,
                'user_id' => null,
                'status' => Device::STATUS_UNBOUND,
            ]);

            $device->unbound_at = now();
            $device->metadata = $metadata;
            $device->save();

            $this->audit->log(
                action: 'devices.unbound',
                auditable: $device,
                actor: $actor,
                changes: [
                    'before' => $before,
                    'after' => $device->only(['vehicle_id', 'user_id', 'status']),
                ],
                context: [
                    'reason' => $reason,
                    'provider_unbind_pending' => true,
                ],
                severity: 'notice',
            );

            return $device->fresh();
        });
    }

    public function isBound(Device $device): bool
    {
        return $device->vehicle_id !== null;
    }
}
