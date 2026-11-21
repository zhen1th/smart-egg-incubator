<?php
/**
 * Device and System Configuration
 * Smart Egg Incubator IoT System
 */

declare(strict_types=1);

// Standard Incubator Temperature Boundaries (in Celsius)
define('TEMP_LOWER_THRESHOLD', 37.0); // Below 37.0°C: Heater ON, Fan OFF
define('TEMP_UPPER_THRESHOLD', 38.0); // Above 38.0°C: Heater OFF, Fan ON

// Device Connectivity Timeouts
define('DEVICE_OFFLINE_THRESHOLD_SECONDS', 10); // Considered offline if no telemetry in 10s

// Data Retention Policy (sensor logs cleanup)
define('DATA_RETENTION_ENABLED', true);
define('DATA_RETENTION_DAYS', 7);
define('CLEANUP_PROBABILITY_PERCENT', 2);

// Stepper Motor Settings
define('STEPPER_SPEED_SPS', 700);
define('STEPPER_MAX_SPEED_SPS', 1000);

/**
 * Stepper Motor Modes:
 * Mode 1: Berputar Terus-Menerus (Continuous Rotation)
 * Mode 2: Berputar Bolak-Balik (Continuous Oscillation)
 * Mode 3: Pembalik Berkala (Periodic 30s)
 */
function getStepperModes(): array
{
    return [
        1 => [
            'id'    => 1,
            'name'  => 'Mode 1: Putar Terus',
            'desc'  => 'Berputar satu arah terus-menerus tanpa henti (700 steps/dtk).',
        ],
        2 => [
            'id'    => 2,
            'name'  => 'Mode 2: Bolak-Balik',
            'desc'  => 'Berputar bolak-balik terus-menerus tanpa jeda diam.',
        ],
        3 => [
            'id'    => 3,
            'name'  => 'Mode 3: Berkala',
            'desc'  => 'Pembalik telur otomatis dengan jeda diam 30 detik.',
        ],
    ];
}
