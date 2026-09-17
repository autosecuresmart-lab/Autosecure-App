<?php

namespace App\Services\Devices\Protocols;

use App\Models\DevicePosition;
use App\Support\Devices\DeviceLocation;
use DateTimeImmutable;
use DateTimeZone;

/**
 * GPRS Communication Protocol Parser & Command Builder.
 *
 * Implements the Shenzhen Sanjitongchuang / 3G GPS terminal protocol
 * specified in "GPRS Communication Protocol for implementing features.md".
 *
 * Packet framing format:
 *   [Manufacturer*DeviceID*LengthInHex*Command,Content...]
 * Example:
 *   [3G*5678901234*0004*FIND]
 */
class GprsProtocolService
{
    /**
     * Escape mappings for AMR audio in intercom (TK) command.
     * Platform -> Terminal escaping:
     * 0x7D -> 0x7D 0x01
     * 0x5B -> 0x7D 0x02
     * 0x5D -> 0x7D 0x03
     * 0x2C -> 0x7D 0x04
     * 0x2A -> 0x7D 0x05
     */
    public const ESCAPE_MAP = [
        "\x7D" => "\x7D\x01",
        "\x5B" => "\x7D\x02",
        "\x5D" => "\x7D\x03",
        "\x2C" => "\x7D\x04",
        "\x2A" => "\x7D\x05",
    ];

    public const UNESCAPE_MAP = [
        "\x7D\x01" => "\x7D",
        "\x7D\x02" => "\x5B",
        "\x7D\x03" => "\x5D",
        "\x7D\x04" => "\x2C",
        "\x7D\x05" => "\x2A",
    ];

    /**
     * Build an outbound command packet for the terminal.
     */
    public function buildPacket(string $deviceId, string $command, string $manufacturer = '3G'): string
    {
        $lenHex = sprintf('%04X', strlen($command));

        return sprintf('[%s*%s*%s*%s]', $manufacturer, $deviceId, $lenHex, $command);
    }

    /**
     * Parse an inbound raw packet string into its structural components.
     *
     * @return array{
     *     manufacturer: string,
     *     device_id: string,
     *     length: int,
     *     command: string,
     *     payload: string,
     *     raw: string
     * }|null
     */
    public function parsePacket(string $rawPacket): ?array
    {
        $trimmed = trim($rawPacket);
        if (! str_starts_with($trimmed, '[') || ! str_ends_with($trimmed, ']')) {
            return null;
        }

        $inner = substr($trimmed, 1, -1);
        $parts = explode('*', $inner, 4);

        if (count($parts) < 4) {
            return null;
        }

        [$manufacturer, $deviceId, $lenHex, $content] = $parts;

        $cmdParts = explode(',', $content, 2);
        $command = $cmdParts[0];
        $payload = $cmdParts[1] ?? '';

        return [
            'manufacturer' => $manufacturer,
            'device_id' => $deviceId,
            'length' => (int) hexdec($lenHex),
            'command' => $command,
            'payload' => $payload,
            'raw' => $trimmed,
        ];
    }

    /**
     * Build a "Find Device / Watch" ring instruction (Command 16).
     */
    public function buildFindCommand(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'FIND', $manufacturer);
    }

    /**
     * Build a "Power Off" instruction (Command 12).
     */
    public function buildPowerOffCommand(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'POWEROFF', $manufacturer);
    }

    /**
     * Build a "Step Counting Time Period" instruction (Command 13).
     *
     * @param  list<string>  $periods  e.g. ['8:10-9:30', '10:10-11:30', '12:10-13:30']
     */
    public function buildWalkTimeCommand(string $deviceId, array $periods, string $manufacturer = '3G'): string
    {
        $payload = 'WALKTIME,'.implode(',', array_slice($periods, 0, 3));

        return $this->buildPacket($deviceId, $payload, $manufacturer);
    }

    /**
     * Build a "Flip / Sleep Detection Time Period" instruction (Command 14).
     */
    public function buildSleepTimeCommand(string $deviceId, string $period, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'SLEEPTIME,'.$period, $manufacturer);
    }

    /**
     * Build a "DND Silence Time Period" instruction (Command 15).
     *
     * @param  list<string>  $periods  up to 4 time ranges
     */
    public function buildSilenceTimeCommand(string $deviceId, array $periods, string $manufacturer = '3G'): string
    {
        $payload = 'SILENCETIME,'.implode(',', array_slice($periods, 0, 4));

        return $this->buildPacket($deviceId, $payload, $manufacturer);
    }

    /**
     * Build an "Alarm Reminders" instruction (Command 17).
     *
     * @param  list<string>  $alarms  e.g. ['08:10-1-1', '08:10-1-2', '08:10-1-3-0111110']
     */
    public function buildRemindCommand(string $deviceId, array $alarms, string $manufacturer = '3G'): string
    {
        $payload = 'REMIND,'.implode(',', array_slice($alarms, 0, 3));

        return $this->buildPacket($deviceId, $payload, $manufacturer);
    }

    /**
     * Build a "Phonebook" instruction (Command 18).
     *
     * @param  list<array{number: string, name: string}>  $contacts
     */
    public function buildPhonebookCommand(string $deviceId, array $contacts, int $page = 1, string $manufacturer = '3G'): string
    {
        $cmdName = $page === 2 ? 'PHB2' : 'PHB';
        $items = [];

        $slice = array_slice($contacts, 0, 5);
        for ($i = 0; $i < 5; $i++) {
            if (isset($slice[$i])) {
                $items[] = $slice[$i]['number'];
                $items[] = bin2hex(mb_convert_encoding($slice[$i]['name'], 'UCS-2BE', 'UTF-8'));
            } else {
                $items[] = '';
                $items[] = '';
            }
        }

        $payload = $cmdName.','.implode(',', $items);

        return $this->buildPacket($deviceId, $payload, $manufacturer);
    }

    /**
     * Build an outbound Intercom Voice Clip (TK) command with AMR escaping.
     */
    public function buildIntercomAudioCommand(string $deviceId, string $rawAmrBytes, string $manufacturer = '3G'): string
    {
        $escaped = strtr($rawAmrBytes, self::ESCAPE_MAP);

        return $this->buildPacket($deviceId, 'TK,'.$escaped, $manufacturer);
    }

    /**
     * Unescape inbound AMR audio received in a TK message.
     */
    public function unescapeAmrAudio(string $escapedAmrBytes): string
    {
        return strtr($escapedAmrBytes, self::UNESCAPE_MAP);
    }

    /**
     * Build a Real-Time GPS Position Poll command (CR).
     */
    public function buildPollPositionCommand(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'CR', $manufacturer);
    }

    /**
     * Build an Upload Frequency Interval command (UPLOAD).
     *
     * @param  int  $intervalSeconds  e.g. 10, 30, 60, 300
     */
    public function buildUploadIntervalCommand(string $deviceId, int $intervalSeconds, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'UPLOAD,'.$intervalSeconds, $manufacturer);
    }

    /**
     * Build an SOS Numbers setting command (SOS).
     *
     * @param  list<string>  $phoneNumbers  up to 3 emergency numbers
     */
    public function buildSosCommand(string $deviceId, array $phoneNumbers, string $manufacturer = '3G'): string
    {
        $payload = 'SOS,'.implode(',', array_slice($phoneNumbers, 0, 3));

        return $this->buildPacket($deviceId, $payload, $manufacturer);
    }

    /**
     * Build an Engine Immobilization / Relay Cutoff command (RELAY).
     *
     * @param  bool  $cut  true to cut engine/fuel (1), false to restore (0)
     */
    public function buildRelayCommand(string $deviceId, bool $cut, string $manufacturer = '3G'): string
    {
        $payload = 'RELAY,'.($cut ? '1' : '0');

        return $this->buildPacket($deviceId, $payload, $manufacturer);
    }

    /**
     * Build a Silent Voice Monitoring Call command (MONITOR).
     */
    public function buildMonitorCommand(string $deviceId, string $phoneNumber, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'MONITOR,'.$phoneNumber, $manufacturer);
    }

    /**
     * Build a Device Reset command (RESET).
     */
    public function buildResetCommand(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'RESET', $manufacturer);
    }

    /**
     * Build a Factory Reset command (FACTORY).
     */
    public function buildFactoryCommand(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'FACTORY', $manufacturer);
    }

    /**
     * Build Language & Timezone command (LZ).
     *
     * @param  int  $language  0 = English, 1 = Chinese
     * @param  int  $timezoneOffsetHours  e.g. 1 for GMT+1 (WAT)
     */
    public function buildTimezoneCommand(string $deviceId, int $language = 0, int $timezoneOffsetHours = 1, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, sprintf('LZ,%d,%d', $language, $timezoneOffsetHours), $manufacturer);
    }

    /**
     * Build Login Handshake Acknowledgment packet (LK).
     */
    public function buildLoginAckPacket(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'LK', $manufacturer);
    }

    /**
     * Build Heartbeat Acknowledgment packet (TK).
     */
    public function buildHeartbeatAckPacket(string $deviceId, string $manufacturer = '3G'): string
    {
        return $this->buildPacket($deviceId, 'TK', $manufacturer);
    }

    /**
     * Parse standard position data payload according to Appendix 1.
     *
     * Format elements:
     * Date (DDMMYY), Time (HHMMSS), Located (A/V), Lat (DD.DDDDDD), LatDir (N/S),
     * Lng (DDD.DDDDDD), LngDir (E/W), Speed (km/h), Direction (deg), Altitude (m),
     * Satellites, GSM signal (0-100), Power (%), Steps, Rolls, TerminalStatus (8 hex chars)...
     *
     * @return array{
     *     location: DeviceLocation,
     *     telemetry: array<string, mixed>
     * }|null
     */
    public function parsePositionData(string $payload): ?array
    {
        $fields = explode(',', $payload);
        if (count($fields) < 16) {
            return null;
        }

        $dateStr = $fields[0]; // e.g. 120414 -> 12 April 2014
        $timeStr = $fields[1]; // e.g. 101930 -> 10:19:30 UTC
        $locateStatus = strtoupper($fields[2]); // A = Valid GPS, V = Invalid / LBS
        $latVal = (float) $fields[3];
        $latDir = strtoupper($fields[4]); // N or S
        $lngVal = (float) $fields[5];
        $lngDir = strtoupper($fields[6]); // E or W
        $speed = (float) $fields[7];
        $direction = (int) $fields[8];
        $altitude = (float) $fields[9];
        $satellites = (int) $fields[10];
        $gsmSignal = (int) $fields[11];
        $powerPercent = (int) $fields[12];
        $stepCount = (int) ($fields[13] ?? 0);
        $rolls = (int) ($fields[14] ?? 0);
        $terminalStatusHex = $fields[15] ?? '00000000';

        $latitude = $latDir === 'S' ? -$latVal : $latVal;
        $longitude = $lngDir === 'W' ? -$lngVal : $lngVal;

        // Parse UTC timestamp: DDMMYY HHMMSS
        $recordedAt = $this->parseTimestamp($dateStr, $timeStr);

        // Decode terminal status bits
        $statusInt = hexdec($terminalStatusHex);
        $alarms = [
            'sos' => (bool) ($statusInt & (1 << 16)),
            'low_battery_alarm' => (bool) ($statusInt & (1 << 17)) || (bool) ($statusInt & (1 << 20)),
            'out_of_fence' => (bool) ($statusInt & (1 << 18)) || (bool) ($statusInt & (1 << 1)),
            'in_fence' => (bool) ($statusInt & (1 << 19)) || (bool) ($statusInt & (1 << 2)),
            'low_power' => (bool) ($statusInt & (1 << 0)),
            'wristband_removed' => (bool) ($statusInt & (1 << 3)),
        ];

        $source = $locateStatus === 'A' ? DevicePosition::SOURCE_GPS : DevicePosition::SOURCE_CELL;

        $location = new DeviceLocation(
            latitude: $latitude,
            longitude: $longitude,
            recordedAt: $recordedAt,
            speedKph: $speed,
            heading: $direction,
            altitudeMetres: $altitude,
            accuracyMetres: $locateStatus === 'A' ? 5.0 : 50.0,
            ignition: $speed > 0,
            moving: $speed > 1.5,
            source: $source,
            raw: [
                'satellites' => $satellites,
                'gsm_csq' => $gsmSignal,
                'power_percent' => $powerPercent,
                'status_hex' => $terminalStatusHex,
                'alarms' => $alarms,
            ],
        );

        return [
            'location' => $location,
            'telemetry' => [
                'satellites' => $satellites,
                'gsm_signal' => $gsmSignal,
                'power_percent' => $powerPercent,
                'step_count' => $stepCount,
                'rolls' => $rolls,
                'status_hex' => $terminalStatusHex,
                'alarms' => $alarms,
                'is_gps_valid' => $locateStatus === 'A',
            ],
        ];
    }

    /**
     * Parse DDMMYY and HHMMSS into DateTimeImmutable (UTC).
     */
    protected function parseTimestamp(string $dateStr, string $timeStr): DateTimeImmutable
    {
        if (strlen($dateStr) === 6 && strlen($timeStr) === 6) {
            $day = substr($dateStr, 0, 2);
            $month = substr($dateStr, 2, 2);
            $year = '20'.substr($dateStr, 4, 2);

            $hour = substr($timeStr, 0, 2);
            $min = substr($timeStr, 2, 2);
            $sec = substr($timeStr, 4, 2);

            $formatted = sprintf('%s-%s-%s %s:%s:%s', $year, $month, $day, $hour, $min, $sec);
            $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $formatted, new DateTimeZone('UTC'));
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
