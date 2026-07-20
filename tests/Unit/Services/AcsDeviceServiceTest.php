<?php

namespace Tests\Unit\Services;

use App\Libraries\ACSRequest;
use App\Services\AcsDeviceService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AcsDeviceServiceTest extends TestCase
{
    #[Test]
    public function it_extracts_and_normalizes_pppoe_usernames_with_rx_power_fallbacks(): void
    {
        $request = $this->createMock(ACSRequest::class);
        $request->expects($this->once())
            ->method('getAllDevices')
            ->willReturn([
                [
                    'VirtualParameters' => [
                        'pppoeUsername' => ['_value' => '  Customer.One  '],
                        'RXPower' => ['_value' => '-20.5'],
                    ],
                ],
                [
                    'VirtualParameters' => [
                        'pppUsername' => ['_value' => 'SECOND@ISP'],
                        'redaman' => ['_value' => -25.2],
                    ],
                ],
                [
                    'InternetGatewayDevice' => [
                        'WANDevice' => [
                            '1' => [
                                'WANConnectionDevice' => [
                                    '1' => [
                                        'WANPPPConnection' => [
                                            '1' => ['Username' => ['_value' => 'Third.User']],
                                        ],
                                    ],
                                ],
                                'WANPONInterfaceConfig' => [
                                    'RXPower' => ['_value' => '-28'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'VirtualParameters' => [
                        'pppoeUsername' => ['_value' => 'missing-power'],
                    ],
                ],
            ]);

        $service = new AcsDeviceService(fn (string $url): ACSRequest => $request);

        $this->assertSame([
            'customer.one' => '-20.5',
            'second@isp' => -25.2,
            'third.user' => '-28',
        ], $service->getRxPowerByPppoeUsername('http://acs.example.test'));
    }

    #[Test]
    public function it_maps_dashboard_devices_using_the_same_fallback_paths(): void
    {
        $request = $this->createStub(ACSRequest::class);
        $request->method('getAllDevices')->willReturn([
            [
                '_id' => 'device-1',
                '_tags' => ['customer'],
                '_lastInform' => date(DATE_ATOM),
                'VirtualParameters' => [
                    'pppUsername' => ['_value' => ' User-A '],
                    'redaman' => ['_value' => '-18.7'],
                    'pppIP' => ['_value' => '192.0.2.10'],
                ],
                'DeviceID' => [
                    'ProductClass' => ['_value' => 'HG8245H'],
                    'SerialNumber' => ['_value' => 'SN123'],
                ],
            ],
        ]);

        $service = new AcsDeviceService(fn (string $url): ACSRequest => $request);
        $device = $service->getDashboardDevices('http://acs.example.test')[0];

        $this->assertSame('device-1', $device['id']);
        $this->assertSame(' User-A ', $device['pppUsername']);
        $this->assertSame('-18.7', $device['rxPower']);
        $this->assertSame('rx-power-good', $device['rxPowerClass']);
        $this->assertSame('192.0.2.10', $device['pppoeIP']);
        $this->assertSame('HG8245H', $device['productClass']);
        $this->assertSame('SN123', $device['serialNumber']);
        $this->assertTrue($device['online']);
    }

    #[Test]
    public function it_rejects_an_invalid_acs_response(): void
    {
        $request = $this->createStub(ACSRequest::class);
        $request->method('getAllDevices')->willReturn(null);

        $service = new AcsDeviceService(fn (string $url): ACSRequest => $request);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Respons API ACS tidak valid.');

        $service->getRxPowerByPppoeUsername('http://acs.example.test');
    }
}
