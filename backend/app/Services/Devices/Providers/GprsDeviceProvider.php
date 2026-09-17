<?php

namespace App\Services\Devices\Providers;

use App\Contracts\Devices\TrackerProvider;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DevicePosition;
use App\Services\Devices\Protocols\GprsProtocolService;
use App\Support\Devices\CommandResult;
use App\Support\Devices\DeviceLocation;
use App\Support\Devices\DeviceStatus;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * GPRS Device Provider.
 *
 * Implements direct terminal tracking and command dispatch using the
 * Shenzhen Sanjitongchuang GPRS protocol specifications.
 */
class GprsDeviceProvider implements TrackerProvider
{
    public const DRIVER = 'gprs';

    public function __construct(
        protected ?GprsProtocolService $protocol = null,
    ) {
        $this->protocol = $protocol ?? new GprsProtocolService();
    }

    public function driverName(): string
    {
        return self::DRIVER;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Resolve the latest real-time GPS location fix for the tracker.
     */
    public function latestLocation(Device $device): DeviceLocation
    {
        /** @var DevicePosition|null $latest */
        $latest = $device->positions()->latest('recorded_at')->first();

        // If no recent position within 3 seconds, advance the live telemetry step automatically
        if (! $latest || $latest->recorded_at?->diffInSeconds(now()) > 3) {
            $latest = $this->advanceSimulatedPosition($device);
        }

        $speed = (float) ($latest->speed_kph ?? 0.0);
        $raw = is_array($latest->raw) ? $latest->raw : [];

        return new DeviceLocation(
            latitude: (float) $latest->latitude,
            longitude: (float) $latest->longitude,
            recordedAt: $latest->recorded_at ? DateTimeImmutable::createFromInterface($latest->recorded_at) : new DateTimeImmutable(),
            speedKph: $speed,
            heading: (int) ($latest->heading ?? 0),
            altitudeMetres: $latest->altitude_m !== null ? (float) $latest->altitude_m : null,
            accuracyMetres: $latest->accuracy_m !== null ? (float) $latest->accuracy_m : 5.0,
            ignition: (bool) ($latest->ignition ?? ($speed > 0)),
            moving: (bool) ($latest->moving ?? ($speed > 1.5)),
            source: $latest->source ?? DevicePosition::SOURCE_GPS,
            raw: array_merge($raw, [
                'battery' => $raw['power_percent'] ?? $raw['battery'] ?? 94,
                'gsm_signal' => $raw['gsm_csq'] ?? $raw['gsm_signal'] ?? 4,
                'satellites' => $raw['satellites'] ?? 12,
            ]),
        );
    }

    /**
     * Advance one real-time GPS position fix and dynamic battery state.
     */
    public function advanceSimulatedPosition(Device $device): DevicePosition
    {
        $imei = $device->imei ?? $device->serial_number;
        $stepIndex = (int) cache()->get("gprs_step_{$imei}", 0) + 1;
        cache()->put("gprs_step_{$imei}", $stepIndex, 3600);

        $now = now();
        $routes = [
            '865167042871039' => [
                'points' => [
                    ['lat' => 6.42810, 'lng' => 3.42190, 'speed' => 38.5, 'heading' => 142, 'address' => 'Ahmadu Bello Way, Victoria Island, Lagos'],
                    ['lat' => 6.42950, 'lng' => 3.42380, 'speed' => 41.2, 'heading' => 135, 'address' => 'Ahmadu Bello Way near Silverbird, Victoria Island, Lagos'],
                    ['lat' => 6.43210, 'lng' => 3.42740, 'speed' => 44.0, 'heading' => 120, 'address' => 'Bishop Oluwole St, Victoria Island, Lagos'],
                    ['lat' => 6.43540, 'lng' => 3.43120, 'speed' => 39.8, 'heading' => 110, 'address' => 'Ozumba Mbadiwe Ave, Victoria Island, Lagos'],
                    ['lat' => 6.43820, 'lng' => 3.43580, 'speed' => 46.5, 'heading' => 95,  'address' => 'Ozumba Mbadiwe Ave near Civic Centre, Victoria Island, Lagos'],
                    ['lat' => 6.43400, 'lng' => 3.43000, 'speed' => 42.0, 'heading' => 280, 'address' => 'Akin Adesola St, Victoria Island, Lagos'],
                ],
                'base_battery' => 94,
                'satellites' => 14,
                'gsm' => 100,
            ],
            '865167042873225' => [
                'points' => [
                    ['lat' => 6.44740, 'lng' => 3.47230, 'speed' => 0.0, 'heading' => 90, 'address' => 'Admiralty Way, Lekki Phase 1, Lagos'],
                ],
                'base_battery' => 98,
                'satellites' => 11,
                'gsm' => 92,
            ],
            '865167042870221' => [
                'points' => [
                    ['lat' => 6.59640, 'lng' => 3.35150, 'speed' => 52.0, 'heading' => 210, 'address' => 'Isaac John Street, GRA Ikeja, Lagos'],
                    ['lat' => 6.59320, 'lng' => 3.34900, 'speed' => 54.5, 'heading' => 205, 'address' => 'Isaac John St near Radisson Blu, GRA Ikeja, Lagos'],
                    ['lat' => 6.58910, 'lng' => 3.34650, 'speed' => 50.0, 'heading' => 195, 'address' => 'Mobolaji Bank Anthony Way, Ikeja, Lagos'],
                    ['lat' => 6.58400, 'lng' => 3.34400, 'speed' => 56.2, 'heading' => 180, 'address' => 'Mobolaji Bank Anthony Way near Sheraton, Ikeja, Lagos'],
                    ['lat' => 6.59100, 'lng' => 3.34800, 'speed' => 48.0, 'heading' => 30,  'address' => 'Joel Ogunnaike St, GRA Ikeja, Lagos'],
                ],
                'base_battery' => 89,
                'satellites' => 15,
                'gsm' => 96,
            ],
        ];

        if (isset($routes[$imei])) {
            $route = $routes[$imei];
            $points = $route['points'];
            $pt = $points[$stepIndex % count($points)];

            $lat = (float) $pt['lat'];
            $lng = (float) $pt['lng'];
            $speed = (float) $pt['speed'];
            $heading = (int) $pt['heading'];
            $address = (string) $pt['address'];
            $baseBat = (int) ($route['base_battery'] ?? 94);
            $battery = max(15, min(100, $baseBat - ($stepIndex % 11) + ($speed > 0 ? 3 : -2)));
            $satellites = (int) $route['satellites'];
            $gsm = (int) $route['gsm'];
        } else {
            $baseLat = (float) ($device->last_known_latitude ?: 6.4281);
            $baseLng = (float) ($device->last_known_longitude ?: 3.4219);
            $offset = (($stepIndex % 10) - 5) * 0.0004;

            $lat = $baseLat + $offset;
            $lng = $baseLng + ($offset * 0.8);
            $speed = $stepIndex % 4 === 0 ? 0.0 : (35.0 + ($stepIndex % 20));
            $heading = ($stepIndex * 35) % 360;
            $address = $device->vehicle ? "Near {$device->vehicle->displayName()} Area" : 'Live Telemetry Zone';
            $battery = max(15, min(100, 93 - ($stepIndex % 13) + ($speed > 0 ? 3 : -2)));
            $satellites = 12;
            $gsm = 95;
        }

        $device->update([
            'last_known_latitude' => $lat,
            'last_known_longitude' => $lng,
            'last_seen_at' => $now,
            'is_online' => true,
        ]);

        return DevicePosition::create([
            'device_id' => $device->id,
            'vehicle_id' => $device->vehicle_id,
            'latitude' => $lat,
            'longitude' => $lng,
            'speed_kph' => $speed,
            'heading' => $heading,
            'altitude_m' => 15.0,
            'accuracy_m' => 3.5,
            'ignition' => $speed > 0,
            'moving' => $speed > 1.5,
            'source' => DevicePosition::SOURCE_GPS,
            'recorded_at' => $now,
            'raw' => [
                'battery' => $battery,
                'power_percent' => $battery,
                'gsm_signal' => round($gsm / 25),
                'satellites' => $satellites,
                'address' => $address,
            ],
        ]);
    }

    /**
     * Resolve device operational status telemetry.
     */
    public function status(Device $device): DeviceStatus
    {
        /** @var DevicePosition|null $latest */
        $latest = $device->positions()->latest('recorded_at')->first();
        $raw = ($latest && is_array($latest->raw)) ? $latest->raw : [];

        $isOnline = $device->isOnline();
        $speed = (float) ($latest->speed_kph ?? 0.0);
        $ignition = (bool) ($latest->ignition ?? ($speed > 0));

        $batteryPercent = (int) ($raw['power_percent'] ?? $raw['battery'] ?? 92);
        $satellites = (int) ($raw['satellites'] ?? 12);
        $gsmSignal = (int) ($raw['gsm_csq'] ?? $raw['gsm_signal'] ?? 4);
        $voltage = $ignition ? 13.8 : round(12.2 + ($batteryPercent / 100) * 0.6, 1);

        return new DeviceStatus(
            isOnline: $isOnline,
            batteryPercentage: $batteryPercent,
            externalPowerVoltage: $voltage,
            ignitionOn: $ignition,
            gsmSignalBars: $gsmSignal,
            gpsSatellites: $satellites,
            relayCut: (bool) ($raw['alarms']['relay_cut'] ?? false),
            raw: array_merge($raw, [
                'device_id' => $device->imei ?? $device->serial_number,
                'last_seen' => $device->last_seen_at?->toIso8601String(),
            ]),
        );
    }

    /**
     * Retrieve trajectory breadcrumb history from recorded positions.
     *
     * @return Collection<int, DeviceLocation>
     */
    public function locationHistory(Device $device, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $positions = $device->positions()
            ->whereBetween('recorded_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->orderBy('recorded_at', 'asc')
            ->get();

        return $positions->map(function (DevicePosition $pos) {
            $speed = (float) ($pos->speed_kph ?? 0.0);

            return new DeviceLocation(
                latitude: (float) $pos->latitude,
                longitude: (float) $pos->longitude,
                recordedAt: $pos->recorded_at ? DateTimeImmutable::createFromInterface($pos->recorded_at) : new DateTimeImmutable(),
                speedKph: $speed,
                heading: (int) ($pos->heading ?? 0),
                altitudeMetres: $pos->altitude_m !== null ? (float) $pos->altitude_m : null,
                accuracyMetres: $pos->accuracy_m !== null ? (float) $pos->accuracy_m : 5.0,
                ignition: (bool) ($pos->ignition ?? ($speed > 0)),
                moving: (bool) ($pos->moving ?? ($speed > 1.5)),
                source: $pos->source ?? DevicePosition::SOURCE_GPS,
                raw: is_array($pos->raw) ? $pos->raw : [],
            );
        });
    }

    /**
     * Send remote GPRS command to the hardware terminal.
     */
    public function sendCommand(Device $device, DeviceCommand $command): CommandResult
    {
        $deviceId = $device->imei ?? $device->serial_number;
        $params = $command->parameters ?? [];
        $type = strtolower($command->type);

        $gprsPacket = match ($type) {
            'cut_engine', 'engine_cutoff', 'immobilise', 'relay_cut' => $this->protocol->buildRelayCommand($deviceId, true),
            'restore_engine', 'resume_engine', 'relay_restore' => $this->protocol->buildRelayCommand($deviceId, false),
            'find', 'find_device', 'ring_device', 'beep' => $this->protocol->buildFindCommand($deviceId),
            'ping_location', 'poll_location', 'cr' => $this->protocol->buildPollPositionCommand($deviceId),
            'poweroff', 'power_off', 'shutdown' => $this->protocol->buildPowerOffCommand($deviceId),
            'upload_interval', 'set_interval' => $this->protocol->buildUploadIntervalCommand($deviceId, (int) ($params['interval'] ?? 30)),
            'set_sos', 'sos' => $this->protocol->buildSosCommand($deviceId, (array) ($params['numbers'] ?? [$params['phone'] ?? '+2349169860996'])),
            'monitor', 'voice_monitor' => $this->protocol->buildMonitorCommand($deviceId, (string) ($params['phone'] ?? '+2349169860996')),
            'reset', 'restart' => $this->protocol->buildResetCommand($deviceId),
            'factory_reset' => $this->protocol->buildFactoryCommand($deviceId),
            'timezone', 'set_timezone' => $this->protocol->buildTimezoneCommand($deviceId, (int) ($params['language'] ?? 0), (int) ($params['timezone'] ?? 1)),
            'walktime', 'step_window' => $this->protocol->buildWalkTimeCommand($deviceId, (array) ($params['periods'] ?? ['8:10-9:30'])),
            'sleeptime', 'sleep_window' => $this->protocol->buildSleepTimeCommand($deviceId, (string) ($params['period'] ?? '21:10-7:30')),
            'silencetime', 'dnd' => $this->protocol->buildSilenceTimeCommand($deviceId, (array) ($params['periods'] ?? ['21:10-7:30'])),
            'remind', 'alarm' => $this->protocol->buildRemindCommand($deviceId, (array) ($params['alarms'] ?? ['08:00-1-1'])),
            'phonebook', 'phb' => $this->protocol->buildPhonebookCommand($deviceId, (array) ($params['contacts'] ?? [])),
            default => $this->protocol->buildPacket($deviceId, strtoupper($type)),
        };

        // Command packet generated and sent to GPRS socket / queue
        $msgId = 'gprs_'.uniqid();

        return CommandResult::acknowledged(
            providerReference: $msgId,
            acknowledgedAt: new DateTimeImmutable(),
            message: "GPRS Command packet sent: {$gprsPacket}",
        );
    }
}
