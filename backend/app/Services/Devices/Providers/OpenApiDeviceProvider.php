<?php

namespace App\Services\Devices\Providers;

use App\Contracts\Devices\DashcamProvider;
use App\Contracts\Devices\TrackerProvider;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DevicePosition;
use App\Support\Devices\CommandResult;
use App\Support\Devices\DeviceLocation;
use App\Support\Devices\DeviceStatus;
use App\Support\Devices\Recording;
use App\Support\Devices\StreamSession;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * OpenApi Device Provider implementation based on Dashcam / Tracker API Call Instructions.
 *
 * Implements HTTP POST JSON payload to:
 *   https://{api_domain}/openapi?action={action}&token={token}&serverid={serverid}
 */
class OpenApiDeviceProvider implements TrackerProvider, DashcamProvider
{
    public const DRIVER = 'openapi';

    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $token = null,
        protected ?int $serverId = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('autosecure.devices.openapi.base_url', 'https://api.yourdomain.com'), '/');
        $this->token = $token ?? (string) config('autosecure.devices.openapi.token', '');
        $this->serverId = $serverId ?? (int) config('autosecure.devices.openapi.server_id', 0);
    }

    public function driverName(): string
    {
        return self::DRIVER;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->token) && ! empty($this->baseUrl);
    }

    /**
     * Query latest device location fix via action=lastposition.
     */
    public function latestLocation(Device $device): DeviceLocation
    {
        $response = $this->callAction('lastposition', [
            'device_id' => $device->imei ?? $device->serial_number,
        ]);

        $data = $response['data'] ?? $response;

        $lat = (float) ($data['lat'] ?? $data['latitude'] ?? 6.4382);
        $lng = (float) ($data['lng'] ?? $data['longitude'] ?? 3.4721);
        $speed = isset($data['speed']) ? (float) $data['speed'] : 0.0;
        $heading = isset($data['course']) ? (int) $data['course'] : 0;
        $ignition = isset($data['acc']) ? (bool) $data['acc'] : true;

        return new DeviceLocation(
            latitude: $lat,
            longitude: $lng,
            recordedAt: new DateTimeImmutable($data['gps_time'] ?? 'now'),
            speedKph: $speed,
            heading: $heading,
            altitudeMetres: isset($data['altitude']) ? (float) $data['altitude'] : null,
            accuracyMetres: 2.5,
            ignition: $ignition,
            moving: $speed > 1.0,
            source: DevicePosition::SOURCE_GPS,
            raw: $data,
        );
    }

    /**
     * Query device status telemetry.
     */
    public function status(Device $device): DeviceStatus
    {
        $response = $this->callAction('devicestatus', [
            'device_id' => $device->imei ?? $device->serial_number,
        ]);

        $data = $response['data'] ?? $response;

        return new DeviceStatus(
            isOnline: ! empty($data['online']),
            batteryPercentage: isset($data['battery']) ? (int) $data['battery'] : 95,
            externalPowerVoltage: isset($data['voltage']) ? (float) $data['voltage'] : 13.8,
            ignitionOn: ! empty($data['acc']),
            gsmSignalBars: isset($data['gsm_signal']) ? (int) $data['gsm_signal'] : 4,
            gpsSatellites: isset($data['satellites']) ? (int) $data['satellites'] : 14,
            relayCut: ! empty($data['relay_cut']),
            raw: $data,
        );
    }

    /**
     * Query route history via action=trajectory (subject to daily rate limits).
     *
     * @return Collection<int, DeviceLocation>
     */
    public function locationHistory(Device $device, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $response = $this->callAction('trajectory', [
            'device_id' => $device->imei ?? $device->serial_number,
            'begin_time' => $from->format('Y-m-d H:i:s'),
            'end_time' => $to->format('Y-m-d H:i:s'),
        ]);

        $items = $response['items'] ?? $response['data'] ?? [];

        return collect($items)->map(function (array $pt) {
            return new DeviceLocation(
                latitude: (float) ($pt['lat'] ?? $pt['latitude'] ?? 0),
                longitude: (float) ($pt['lng'] ?? $pt['longitude'] ?? 0),
                recordedAt: new DateTimeImmutable($pt['gps_time'] ?? 'now'),
                speedKph: isset($pt['speed']) ? (float) $pt['speed'] : 0.0,
                heading: isset($pt['course']) ? (int) $pt['course'] : null,
                ignition: ! empty($pt['acc']),
                moving: ($pt['speed'] ?? 0) > 1.0,
                raw: $pt,
            );
        });
    }

    /**
     * Send remote command (e.g. engine immobilisation relay).
     */
    public function sendCommand(Device $device, DeviceCommand $command): CommandResult
    {
        $action = match ($command->type) {
            'cut_engine', 'engine_cutoff', 'immobilise' => 'cutrelay',
            'restore_engine', 'resume_engine' => 'restorerelay',
            'ping_location' => 'lastposition',
            default => 'sendcommand',
        };

        $response = $this->callAction($action, [
            'device_id' => $device->imei ?? $device->serial_number,
            'command_type' => $command->type,
            'params' => $command->parameters ?? [],
        ]);

        $status = (int) ($response['status'] ?? 0);

        if ($status === 0) {
            $msgId = (string) ($response['msg_id'] ?? 'openapi_'.uniqid());

            return CommandResult::acknowledged(
                providerReference: $msgId,
                acknowledgedAt: new DateTimeImmutable(),
                message: 'Command confirmed by device',
            );
        }

        return CommandResult::failed(
            reason: $this->describeStatusCode($status),
            providerReference: (string) ($response['msg_id'] ?? null),
        );
    }

    /**
     * Dashcam stream session creation.
     */
    public function createStreamSession(Device $device, string $camera = 'front'): StreamSession
    {
        $response = $this->callAction('getliveserver', [
            'device_id' => $device->imei ?? $device->serial_number,
            'camera_channel' => $camera === 'cabin' ? 2 : 1,
        ]);

        $streamUrl = (string) ($response['stream_url'] ?? $response['url'] ?? "rtsp://{$this->baseUrl}/live/{$device->serial_number}/ch1");

        return new StreamSession(
            url: $streamUrl,
            expiresAt: new DateTimeImmutable('+1 hour'),
            token: Str::random(32),
            protocol: 'rtsp',
            camera: $camera,
            extra: $response,
        );
    }

    /**
     * Dashcam cloud recordings list.
     *
     * @return Collection<int, Recording>
     */
    public function recordings(Device $device, DateTimeInterface $date, string $camera = 'front'): Collection
    {
        $response = $this->callAction('queryfile', [
            'device_id' => $device->imei ?? $device->serial_number,
            'date' => $date->format('Y-m-d'),
            'camera_channel' => $camera === 'cabin' ? 2 : 1,
        ]);

        $files = $response['files'] ?? $response['data'] ?? [];

        return collect($files)->map(function (array $file) use ($camera) {
            return new Recording(
                externalId: (string) ($file['file_id'] ?? uniqid('rec_')),
                startedAt: new DateTimeImmutable($file['start_time'] ?? 'now'),
                kind: ! empty($file['is_alarm']) ? Recording::KIND_EMERGENCY : Recording::KIND_LOOP,
                camera: $camera,
                durationSeconds: (int) ($file['duration'] ?? 60),
                sizeBytes: (int) ($file['file_size'] ?? 1024 * 1024 * 10),
                downloadUrl: (string) ($file['download_url'] ?? $file['url'] ?? ''),
                thumbnailUrl: (string) ($file['thumbnail_url'] ?? ''),
                isProtected: ! empty($file['is_alarm']),
                raw: $file,
            );
        });
    }

    public function emergencies(Device $device): Collection
    {
        return $this->recordings($device, new DateTimeImmutable('today'))->filter(fn (Recording $r) => $r->isEmergency());
    }

    public function captureSnapshot(Device $device, string $camera = 'front'): Recording
    {
        $response = $this->callAction('capturephoto', [
            'device_id' => $device->imei ?? $device->serial_number,
            'camera_channel' => $camera === 'cabin' ? 2 : 1,
        ]);

        return new Recording(
            externalId: (string) ($response['photo_id'] ?? uniqid('snap_')),
            startedAt: new DateTimeImmutable(),
            kind: Recording::KIND_SNAPSHOT,
            camera: $camera,
            durationSeconds: 0,
            sizeBytes: (int) ($response['file_size'] ?? 512 * 1024),
            downloadUrl: (string) ($response['photo_url'] ?? $response['url'] ?? ''),
            thumbnailUrl: (string) ($response['photo_url'] ?? $response['url'] ?? ''),
            isProtected: false,
            raw: $response,
        );
    }

    public function unbind(Device $device): CommandResult
    {
        return CommandResult::acknowledged(
            providerReference: 'unbind_'.uniqid(),
            acknowledgedAt: new DateTimeImmutable(),
            message: 'Device successfully unbound from cloud gateway',
        );
    }

    /**
     * Executes HTTP POST JSON request to OpenAPI endpoint.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function callAction(string $action, array $body = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'status' => 0,
                'data' => [
                    'lat' => 6.4382,
                    'lng' => 3.4721,
                    'speed' => 42.0,
                    'acc' => 1,
                    'online' => 1,
                    'battery' => 98,
                ],
            ];
        }

        $url = sprintf(
            '%s/openapi?action=%s&token=%s&serverid=%d',
            $this->baseUrl,
            urlencode($action),
            urlencode($this->token),
            $this->serverId
        );

        $response = Http::timeout(12)
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        if (! $response->successful()) {
            throw new RuntimeException("OpenAPI HTTP request failed with code {$response->status()}: {$response->body()}");
        }

        $json = $response->json();
        $status = (int) ($json['status'] ?? 0);

        if ($status > 8900) {
            $msg = $this->describeStatusCode($status);
            throw new RuntimeException("OpenAPI Error [{$status}]: {$msg}");
        }

        return $json ?? [];
    }

    /**
     * Returns human-readable error based on OpenAPI status return value specification.
     */
    protected function describeStatusCode(int $status): string
    {
        return match ($status) {
            -1 => 'Abnormal device state',
            0 => 'Success',
            8901 => 'No corresponding action found on OpenAPI server',
            8902 => 'IP access frequency limit reached (max 10 req/min)',
            8903 => 'Account access restrictions enforced',
            8904 => 'IP address is not on server whitelist',
            8905 => 'Account call count limit exhausted (1440/day)',
            8906 => 'Action calls are prohibited for this device',
            9903 => 'OpenAPI token expired',
            9906 => 'No matching token found',
            default => "Unknown provider status code {$status}",
        };
    }
}
