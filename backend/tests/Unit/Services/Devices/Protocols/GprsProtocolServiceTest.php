<?php

namespace Tests\Unit\Services\Devices\Protocols;

use App\Models\DevicePosition;
use App\Services\Devices\Protocols\GprsProtocolService;
use PHPUnit\Framework\TestCase;

class GprsProtocolServiceTest extends TestCase
{
    protected GprsProtocolService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GprsProtocolService();
    }

    public function test_it_builds_find_command(): void
    {
        $packet = $this->service->buildFindCommand('5678901234');
        $this->assertSame('[3G*5678901234*0004*FIND]', $packet);
    }

    public function test_it_builds_power_off_command(): void
    {
        $packet = $this->service->buildPowerOffCommand('5678901234');
        $this->assertSame('[3G*5678901234*0008*POWEROFF]', $packet);
    }

    public function test_it_builds_walk_time_command(): void
    {
        $packet = $this->service->buildWalkTimeCommand('5678901234', [
            '8:10-9:30',
            '10:10-11:30',
            '12:10-13:30',
        ]);
        $this->assertSame('[3G*5678901234*002A*WALKTIME,8:10-9:30,10:10-11:30,12:10-13:30]', $packet);
    }

    public function test_it_parses_inbound_packets(): void
    {
        $parsed = $this->service->parsePacket('[3G*5678901234*0004*FIND]');
        $this->assertNotNull($parsed);
        $this->assertSame('3G', $parsed['manufacturer']);
        $this->assertSame('5678901234', $parsed['device_id']);
        $this->assertSame('FIND', $parsed['command']);
    }

    public function test_it_escapes_and_unescapes_amr_audio(): void
    {
        $rawAmr = "Header\x7DData\x5BBracket\x5DComma\x2CStar\x2AEnd";
        $escapedPacket = $this->service->buildIntercomAudioCommand('5678901234', $rawAmr);

        $parsed = $this->service->parsePacket($escapedPacket);
        $this->assertNotNull($parsed);
        $this->assertSame('TK', $parsed['command']);

        $unescaped = $this->service->unescapeAmrAudio($parsed['payload']);
        $this->assertSame($rawAmr, $unescaped);
    }

    public function test_it_parses_position_data_appendix_1(): void
    {
        // 120414,101930,A,22.564025,N,113.242329,E,5.21,152,100,9,100,90,1000,50,00010000
        $payload = '120414,101930,A,22.564025,N,113.242329,E,5.21,152,100,9,100,90,1000,50,00010000';
        $result = $this->service->parsePositionData($payload);

        $this->assertNotNull($result);
        $loc = $result['location'];

        $this->assertSame(22.564025, $loc->latitude);
        $this->assertSame(113.242329, $loc->longitude);
        $this->assertSame(5.21, $loc->speedKph);
        $this->assertSame(152, $loc->heading);
        $this->assertSame(100.0, $loc->altitudeMetres);
        $this->assertSame(DevicePosition::SOURCE_GPS, $loc->source);
        $this->assertSame('2014-04-12 10:19:30', $loc->recordedAt->format('Y-m-d H:i:s'));

        // Bit 16 is SOS alarm (00010000 hex = 1 << 16)
        $this->assertTrue($result['telemetry']['alarms']['sos']);
        $this->assertSame(90, $result['telemetry']['power_percent']);
    }

    public function test_it_builds_poll_position_command(): void
    {
        $packet = $this->service->buildPollPositionCommand('865167042871039');
        $this->assertSame('[3G*865167042871039*0002*CR]', $packet);
    }

    public function test_it_builds_upload_interval_command(): void
    {
        $packet = $this->service->buildUploadIntervalCommand('865167042871039', 30);
        $this->assertSame('[3G*865167042871039*0009*UPLOAD,30]', $packet);
    }

    public function test_it_builds_sos_command(): void
    {
        $packet = $this->service->buildSosCommand('865167042871039', ['09169860996', '08012345678']);
        $this->assertSame('[3G*865167042871039*001B*SOS,09169860996,08012345678]', $packet);
    }

    public function test_it_builds_relay_command(): void
    {
        $cutPacket = $this->service->buildRelayCommand('865167042871039', true);
        $this->assertSame('[3G*865167042871039*0007*RELAY,1]', $cutPacket);

        $restorePacket = $this->service->buildRelayCommand('865167042871039', false);
        $this->assertSame('[3G*865167042871039*0007*RELAY,0]', $restorePacket);
    }

    public function test_it_builds_monitor_and_maintenance_commands(): void
    {
        $monPacket = $this->service->buildMonitorCommand('865167042871039', '+2349169860996');
        $this->assertSame('[3G*865167042871039*0016*MONITOR,+2349169860996]', $monPacket);

        $resetPacket = $this->service->buildResetCommand('865167042871039');
        $this->assertSame('[3G*865167042871039*0005*RESET]', $resetPacket);

        $factPacket = $this->service->buildFactoryCommand('865167042871039');
        $this->assertSame('[3G*865167042871039*0007*FACTORY]', $factPacket);

        $tzPacket = $this->service->buildTimezoneCommand('865167042871039', 0, 1);
        $this->assertSame('[3G*865167042871039*0006*LZ,0,1]', $tzPacket);
    }
}
