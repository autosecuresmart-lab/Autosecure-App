<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nickname' => $this->nickname,
            'display_name' => $this->displayName(),
            'plate_number' => $this->plate_number,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'colour' => $this->colour,
            'vin' => $this->vin,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'image_url' => $this->image_path ? url($this->image_path) : null,
            'odometer_km' => $this->odometer_km,
            'odometer_source' => $this->odometer_source,
            'odometer_updated_at' => $this->odometer_updated_at?->toIso8601String(),
            'is_primary' => (bool) $this->is_primary,
            'status' => $this->status,
            'devices' => DeviceResource::collection($this->whenLoaded('devices')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
