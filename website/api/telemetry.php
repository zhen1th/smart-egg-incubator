<?php
/**
 * REST API: Telemetry Ingestion and Decision Engine
 * Endpoint: POST /api/telemetry.php
 * 
 * Communication protocol between ESP32 and Backend PHP Server.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use HTTP POST.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/device_config.php';
require_once __DIR__ . '/../includes/temperature_engine.php';

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Malformed JSON payload.']);
    exit;
}

$headers = getallheaders();
$apiKeyHeader = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? null;
$apiKey = $apiKeyHeader ?: ($data['api_key'] ?? null);

$deviceId     = trim((string)($data['device_id'] ?? ''));
$temperature  = isset($data['temperature']) ? (float)$data['temperature'] : null;
$humidity     = isset($data['humidity']) ? (float)$data['humidity'] : null;
$sensorValid  = isset($data['sensor_valid']) ? (bool)$data['sensor_valid'] : false;
$lampStatus   = isset($data['lamp_status']) ? (int)$data['lamp_status'] : 0;
$fanStatus    = isset($data['fan_status']) ? (int)$data['fan_status'] : 0;

if (empty($deviceId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: device_id.']);
    exit;
}

$pdo = getDbConnection();

// Auto-migrate stepper_mode column if needed
try {
    $pdo->exec("ALTER TABLE `devices` ADD COLUMN `stepper_mode` TINYINT NOT NULL DEFAULT 1 AFTER `control_mode`");
} catch (\Throwable $e) {}

$deviceStmt = $pdo->prepare(
    'SELECT id, device_id, device_name, api_key, last_heater_command, last_fan_command, stepper_mode, status 
     FROM devices 
     WHERE device_id = :device_id LIMIT 1'
);
$deviceStmt->execute([':device_id' => $deviceId]);
$device = $deviceStmt->fetch();

if (!$device) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Device not registered.']);
    exit;
}

if ($device['status'] !== 'ACTIVE') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Device is inactive.']);
    exit;
}

if (empty($apiKey) || !hash_equals((string)$device['api_key'], (string)$apiKey)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid API Key.']);
    exit;
}

$stepperMode = (int)($device['stepper_mode'] ?? 1);

// Sensor fail-safe
if (!$sensorValid || $temperature === null || $humidity === null || $temperature < -10 || $temperature > 85) {
    $safeCommand = ['lamp' => 0, 'fan' => 0, 'stepper_mode' => $stepperMode];

    $updateStmt = $pdo->prepare(
        'UPDATE devices SET 
            is_online = 1,
            last_seen = NOW(),
            actual_lamp_status = :actual_lamp,
            actual_fan_status = :actual_fan,
            last_heater_command = 0,
            last_fan_command = 0
         WHERE device_id = :device_id'
    );
    $updateStmt->execute([
        ':actual_lamp' => $lampStatus,
        ':actual_fan'  => $fanStatus,
        ':device_id'   => $deviceId,
    ]);

    $logStmt = $pdo->prepare(
        'INSERT INTO control_logs (device_id, change_type, heater_status, fan_status, trigger_temperature, description)
         VALUES (:device_id, "SENSOR_FAILSAFE", 0, 0, :temp, "Pembacaan sensor DHT11 gagal. Pengaman aktif: Lampu pemanas dimatikan.")'
    );
    $logStmt->execute([
        ':device_id' => $deviceId,
        ':temp'      => $temperature,
    ]);

    echo json_encode([
        'success'      => true,
        'commands'     => $safeCommand,
        'warning'      => 'Sensor read failed. Safe commands issued.'
    ]);
    exit;
}

// 1. Persist sensor telemetry
$sensorStmt = $pdo->prepare(
    'INSERT INTO sensor_logs (device_id, temperature, humidity, sensor_valid, recorded_at)
     VALUES (:device_id, :temp, :hum, 1, NOW())'
);
$sensorStmt->execute([
    ':device_id' => $deviceId,
    ':temp'      => $temperature,
    ':hum'       => $humidity,
]);

// 2. Evaluate normal temperature rules (37.0°C - 38.0°C)
$prevHeater = (int)$device['last_heater_command'];
$prevFan    = (int)$device['last_fan_command'];
$decision = TemperatureEngine::evaluate($temperature, $prevHeater, $prevFan);

$newHeater = $decision['lamp'];
$newFan    = $decision['fan'];

// 3. Update device state
$updateDevStmt = $pdo->prepare(
    'UPDATE devices SET 
        is_online = 1,
        last_seen = NOW(),
        last_temperature = :temp,
        last_humidity = :hum,
        actual_lamp_status = :actual_lamp,
        actual_fan_status = :actual_fan,
        last_heater_command = :cmd_lamp,
        last_fan_command = :cmd_fan
     WHERE device_id = :device_id'
);
$updateDevStmt->execute([
    ':temp'        => $temperature,
    ':hum'         => $humidity,
    ':actual_lamp' => $lampStatus,
    ':actual_fan'  => $fanStatus,
    ':cmd_lamp'    => $newHeater,
    ':cmd_fan'     => $newFan,
    ':device_id'   => $deviceId,
]);

// 4. Log state change
if ($decision['state_changed']) {
    $ctrlLogStmt = $pdo->prepare(
        'INSERT INTO control_logs (device_id, change_type, heater_status, fan_status, trigger_temperature, description)
         VALUES (:device_id, :change_type, :heater, :fan, :temp, :desc)'
    );
    $ctrlLogStmt->execute([
        ':device_id'   => $deviceId,
        ':change_type' => $decision['change_type'],
        ':heater'      => $newHeater,
        ':fan'         => $newFan,
        ':temp'        => $temperature,
        ':desc'        => $decision['description'],
    ]);
}

// 5. Data retention cleanup
if (DATA_RETENTION_ENABLED && random_int(1, 100) <= CLEANUP_PROBABILITY_PERCENT) {
    try {
        $retentionStmt = $pdo->prepare('DELETE FROM sensor_logs WHERE recorded_at < DATE_SUB(NOW(), INTERVAL :days DAY)');
        $retentionStmt->bindValue(':days', DATA_RETENTION_DAYS, PDO::PARAM_INT);
        $retentionStmt->execute();
    } catch (\Throwable $e) {}
}

// 6. Return response to ESP32 (including stepper_mode)
echo json_encode([
    'success'      => true,
    'commands'     => [
        'lamp'         => $newHeater,
        'fan'          => $newFan,
        'stepper_mode' => $stepperMode,
    ],
    'server_time'  => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_SLASHES);
