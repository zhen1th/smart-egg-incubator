<?php
/**
 * Temperature Control Engine
 * Smart Egg Incubator IoT System
 * 
 * Rules:
 * - Suhu < 37.0 °C: Heater ON, Fan OFF
 * - Suhu > 38.0 °C: Heater OFF, Fan ON
 * - 37.0 °C - 38.0 °C: Pertahankan status heater dan fan sebelumnya (Deadband Histeresis)
 * - Interlock Safety: Heater dan Fan tidak pernah aktif bersamaan
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/device_config.php';

class TemperatureEngine
{
    public static function evaluate(float $temperature, int $prevHeater, int $prevFan): array
    {
        $newHeater = $prevHeater;
        $newFan = $prevFan;
        $changeType = 'MAINTAIN';
        $description = '';

        if ($temperature < TEMP_LOWER_THRESHOLD) {
            $newHeater = 1;
            $newFan = 0;
            $changeType = 'TEMP_LOW';
            $description = sprintf('Suhu %.1f°C < %.1f°C: Lampu pemanas ON, kipas OFF.', $temperature, TEMP_LOWER_THRESHOLD);
        } elseif ($temperature > TEMP_UPPER_THRESHOLD) {
            $newHeater = 0;
            $newFan = 1;
            $changeType = 'TEMP_HIGH';
            $description = sprintf('Suhu %.1f°C > %.1f°C: Lampu pemanas OFF, kipas pendingin ON.', $temperature, TEMP_UPPER_THRESHOLD);
        } else {
            $changeType = 'TEMP_OPTIMAL';
            $description = sprintf('Suhu %.1f°C ideal (%.1f°C - %.1f°C): Status dipertahankan (Lampu %s, Kipas %s).', 
                $temperature, 
                TEMP_LOWER_THRESHOLD, 
                TEMP_UPPER_THRESHOLD, 
                $prevHeater ? 'ON' : 'OFF', 
                $prevFan ? 'ON' : 'OFF'
            );
        }

        // Hardware interlock
        if ($newHeater === 1 && $newFan === 1) {
            $newFan = 0;
            $changeType = 'INTERLOCK_RESOLVED';
            $description .= ' [Interlock: Kipas dimatikan demi keamanan].';
        }

        $stateChanged = ($newHeater !== $prevHeater) || ($newFan !== $prevFan);

        return [
            'lamp'          => $newHeater,
            'fan'           => $newFan,
            'state_changed' => $stateChanged,
            'change_type'   => $changeType,
            'description'   => $description,
        ];
    }
}
