<?php

namespace App\Services;

use App\Libraries\ACSRequest;
use Closure;
use RuntimeException;
use Throwable;

class AcsDeviceService
{
    private const PARAMETER_PATHS = [
        'pppUsername' => [
            'VirtualParameters.pppoeUsername',
            'VirtualParameters.pppUsername',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
        ],
        'rxPower' => [
            'VirtualParameters.RXPower',
            'VirtualParameters.redaman',
            'InternetGatewayDevice.WANDevice.1.WANPONInterfaceConfig.RXPower',
        ],
        'pppoeIP' => [
            'VirtualParameters.pppoeIP',
            'VirtualParameters.pppIP',
        ],
        'ssid' => [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
        ],
        'userConnected' => [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
        ],
        'productClass' => [
            'DeviceID.ProductClass',
            'InternetGatewayDevice.DeviceInfo.ProductClass',
            'Device.DeviceInfo.ProductClass',
            'InternetGatewayDevice.DeviceInfo.ModelName',
        ],
        'serialNumber' => [
            'DeviceID.SerialNumber',
            'InternetGatewayDevice.DeviceInfo.SerialNumber',
            'Device.DeviceInfo.SerialNumber',
        ],
    ];

    public function __construct(private ?Closure $requestFactory = null) {}

    /**
     * Fetch every ACS device once and return the validated response.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllDevices(string $acsUrl): array
    {
        try {
            $response = $this->request($acsUrl)->getAllDevices();
        } catch (Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data dari API ACS.', 0, $exception);
        }

        if (! is_array($response)) {
            throw new RuntimeException('Respons API ACS tidak valid.');
        }

        return array_values(array_filter($response, 'is_array'));
    }

    /**
     * Return RX power indexed by normalized PPPoE username.
     *
     * @return array<string, mixed>
     */
    public function getRxPowerByPppoeUsername(string $acsUrl): array
    {
        $rxPowerByUsername = [];

        foreach ($this->getAllDevices($acsUrl) as $device) {
            $username = self::normalizePppoeUsername($this->parameterValue($device, self::PARAMETER_PATHS['pppUsername']));
            $rxPower = $this->parameterValue($device, self::PARAMETER_PATHS['rxPower']);

            if ($username !== null && $rxPower !== null && $rxPower !== '') {
                $rxPowerByUsername[$username] = $rxPower;
            }
        }

        return $rxPowerByUsername;
    }

    /**
     * Build the device rows consumed by the ACS dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDashboardDevices(string $acsUrl): array
    {
        return array_map(fn (array $device): array => $this->mapDashboardDevice($device), $this->getAllDevices($acsUrl));
    }

    public static function normalizePppoeUsername(mixed $username): ?string
    {
        if (! is_scalar($username)) {
            return null;
        }

        $normalized = strtolower(trim((string) $username));

        return $normalized !== '' ? $normalized : null;
    }

    public function parameterValue(array $device, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = $device;

            foreach (explode('.', $path) as $key) {
                if (! is_array($value) || ! array_key_exists($key, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$key];
            }

            if (is_array($value) && array_key_exists('_value', $value)) {
                return $value['_value'];
            }

            if ($value !== null && ! is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    private function request(string $acsUrl): ACSRequest
    {
        if ($this->requestFactory !== null) {
            $request = ($this->requestFactory)($acsUrl);

            if (! $request instanceof ACSRequest) {
                throw new RuntimeException('ACS request factory must return ACSRequest.');
            }

            return $request;
        }

        return new ACSRequest($acsUrl);
    }

    private function mapDashboardDevice(array $device): array
    {
        $lastInform = $device['_lastInform'] ?? null;
        $rxPower = $this->parameterValue($device, self::PARAMETER_PATHS['rxPower']) ?? 'N/A';

        return [
            'id' => $device['_id'] ?? 'N/A',
            'tags' => $device['_tags'] ?? [],
            'online' => $this->isOnline($lastInform),
            'lastinform' => $this->formatLastInform($lastInform),
            'pppUsername' => $this->parameterValue($device, self::PARAMETER_PATHS['pppUsername']) ?? 'Unknown',
            'pppoeIP' => $this->parameterValue($device, self::PARAMETER_PATHS['pppoeIP']) ?? 'N/A',
            'rxPower' => $rxPower,
            'rxPowerClass' => $this->rxPowerClass($rxPower),
            'ssid' => $this->parameterValue($device, self::PARAMETER_PATHS['ssid']) ?? '',
            'userConnected' => $this->parameterValue($device, self::PARAMETER_PATHS['userConnected']) ?? '0',
            'productClass' => $this->parameterValue($device, self::PARAMETER_PATHS['productClass']) ?? 'N/A',
            'serialNumber' => $this->parameterValue($device, self::PARAMETER_PATHS['serialNumber']) ?? 'N/A',
        ];
    }

    private function isOnline(mixed $lastInform): bool
    {
        if (! is_string($lastInform) || strtotime($lastInform) === false) {
            return false;
        }

        return (time() - strtotime($lastInform)) <= 5 * 60;
    }

    private function formatLastInform(mixed $lastInform): string
    {
        if (! is_string($lastInform) || strtotime($lastInform) === false) {
            return 'N/A';
        }

        return date('d F Y, H:i', strtotime($lastInform));
    }

    private function rxPowerClass(mixed $rxPower): string
    {
        if (! $rxPower) {
            return '';
        }

        $power = (float) $rxPower;

        if ($power > -8) {
            return 'rx-power-critical';
        }

        if ($power < -8 && $power > -27) {
            return 'rx-power-good';
        }

        return $power <= -27 ? 'rx-power-critical' : '';
    }
}
