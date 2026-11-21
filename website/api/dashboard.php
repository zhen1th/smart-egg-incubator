<?php
/**
 * REST API: Dashboard Real-time State Endpoint
 * Endpoint: GET /api/dashboard.php
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/device_config.php';

$deviceId = trim((string)($_GET['device_id'] ?? 'INCUBATOR_01'));
$pdo = getDbConnection();

// Fetch device status
$deviceStmt = $pdo->prepare(
    'SELECT 
        device_id,
        device_name,
        is_online,
        last_seen,
        TIMESTAMPDIFF(SECOND, last_seen, NOW()) AS seconds_since_last_seen,
        last_temperature,
        last_humidity,
        last_heater_command,
        last_fan_command,
        actual_lamp_status,
        actual_fan_status,
        stepper_mode,
        status,
        updated_at
     FROM devices 
     WHERE device_id = :device_id LIMIT 1'
);
$deviceStmt->execute([':device_id' => $deviceId]);
$device = $deviceStmt->fetch();

if (!$device) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Device not found.']);
    exit;
}

$secondsAgo = $device['seconds_since_last_seen'] !== null ? (int)$device['seconds_since_last_seen'] : 999999;
$isLiveOnline = ($device['last_seen'] !== null) && ($secondsAgo <= DEVICE_OFFLINE_THRESHOLD_SECONDS);
$stepperMode = (int)($device['stepper_mode'] ?? 1);

// Temperature condition
$temp = $device['last_temperature'] !== null ? (float)$device['last_temperature'] : null;
$tempStatus = 'UNKNOWN';
if ($temp !== null) {
    if ($temp < TEMP_LOWER_THRESHOLD) {
        $tempStatus = 'COLD';
    } elseif ($temp > TEMP_UPPER_THRESHOLD) {
        $tempStatus = 'HOT';
    } else {
        $tempStatus = 'OPTIMAL';
    }
}

// Fetch latest log
$logStmt = $pdo->prepare(
    'SELECT change_type, heater_status, fan_status, trigger_temperature, description, created_at
     FROM control_logs
     WHERE device_id = :device_id
     ORDER BY id DESC LIMIT 1'
);
$logStmt->execute([':device_id' => $deviceId]);
$latestLog = $logStmt->fetch() ?: null;

echo json_encode([
    'success' => true,
    'data'    => [
        'device' => [
            'device_id'          => $device['device_id'],
            'is_online'          => $isLiveOnline,
            'seconds_since_seen' => $secondsAgo,
            'last_seen'          => $device['last_seen'],
        ],
        'stepper_mode' => $stepperMode,
        'telemetry' => [
            'temperature' => $temp,
            'humidity'    => $device['last_humidity'] !== null ? (float)$device['last_humidity'] : null,
            'temp_status' => $tempStatus,
            'target_min'  => TEMP_LOWER_THRESHOLD,
            'target_max'  => TEMP_UPPER_THRESHOLD,
        ],
        'actuators' => [
            'command_heater' => (int)$device['last_heater_command'],
            'command_fan'    => (int)$device['last_fan_command'],
            'actual_lamp'    => (int)$device['actual_lamp_status'],
            'actual_fan'     => (int)$device['actual_fan_status'],
            'stepper_mode'   => $stepperMode,
        ],
        'latest_activity' => $latestLog,
        'server_time'     => date('Y-m-d H:i:s'),
    ]
], JSON_UNESCAPED_SLASHES);
