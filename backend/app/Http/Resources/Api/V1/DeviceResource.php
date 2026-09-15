<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Device payload.
 *
 * Provider credentials, external ids and raw capability blobs are deliberately
 * not exposed to the mobile client.
 */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'label' => $this->label,
            'display_name' => $this->displayName(),
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'firmware_version' => $this->firmware_version,
            'status' => $this->status,
            'is_online' => (bool) $this->is_online,
            'is_bound' => $this->vehicle_id !== null,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'uuid' => $this->vehicle->uuid,
                'display_name' => $this->vehicle->displayName(),
                'plate_number' => $this->vehicle->plate_number,
            ]),
            'bound_at' => $this->bound_at?->toIso8601String(),
            'unbound_at' => $this->unbound_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'last_known_position' => $this->last_known_latitude === null ? null : [
                'latitude' => (float) $this->last_known_latitude,
                'longitude' => (float) $this->last_known_longitude,
            ],
            'capabilities' => $this->capabilities,
        ];
    }
}
