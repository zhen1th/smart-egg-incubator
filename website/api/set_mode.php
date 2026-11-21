<?php
/**
 * REST API: Set Stepper Motor Mode Endpoint
 * Endpoint: POST /api/set_mode.php
 * 
 * Switches the stepper motor mode (Mode 1, Mode 2, Mode 3).
 * Persists selection to MySQL database and logs the audit event with current temp & relay states.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Gunakan HTTP POST.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/device_config.php';

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);
if (!is_array($data)) {
    $data = $_POST;
}

$deviceId = trim((string)($data['device_id'] ?? 'INCUBATOR_01'));
$modeInput = (int)($data['mode'] ?? 1);

if ($modeInput < 1 || $modeInput > 3) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Mode tidak valid. Pilihan: 1 (Putar Terus), 2 (Bolak-Balik), 3 (Berkala).'
    ]);
    exit;
}

$modes = getStepperModes();
$selectedMode = $modes[$modeInput];
$pdo = getDbConnection();

try {
    // Ensure column stepper_mode exists
    try {
        $pdo->exec("ALTER TABLE `devices` ADD COLUMN `stepper_mode` TINYINT NOT NULL DEFAULT 1 AFTER `control_mode`");
    } catch (\Throwable $e) {}

    $pdo->beginTransaction();

    // 1. Update device stepper mode
    $stmt = $pdo->prepare('UPDATE devices SET stepper_mode = :mode, updated_at = NOW() WHERE device_id = :id');
    $stmt->execute([
        ':mode' => $modeInput,
        ':id'   => $deviceId,
    ]);

    // 2. Query current temperature, heater, and fan status from devices & sensor_logs
    $devStmt = $pdo->prepare(
        'SELECT last_temperature, actual_lamp_status, actual_fan_status, last_heater_command, last_fan_command 
         FROM devices WHERE device_id = :id LIMIT 1'
    );
    $devStmt->execute([':id' => $deviceId]);
    $currentDev = $devStmt->fetch();

    $currentTemp = $currentDev && $currentDev['last_temperature'] !== null 
        ? (float)$currentDev['last_temperature'] 
        : null;

    // Fallback to latest sensor log if device table temp is null
    if ($currentTemp === null) {
        $sensorStmt = $pdo->prepare('SELECT temperature FROM sensor_logs WHERE device_id = :id ORDER BY id DESC LIMIT 1');
        $sensorStmt->execute([':id' => $deviceId]);
        $latestSensor = $sensorStmt->fetch();
        if ($latestSensor && isset($latestSensor['temperature'])) {
            $currentTemp = (float)$latestSensor['temperature'];
        }
    }

    $currentHeater = $currentDev ? (int)($currentDev['actual_lamp_status'] ?? $currentDev['last_heater_command'] ?? 0) : 0;
    $currentFan    = $currentDev ? (int)($currentDev['actual_fan_status'] ?? $currentDev['last_fan_command'] ?? 0) : 0;

    // 3. Record audit trail in control_logs with current temperature, lamp, and fan state
    $desc = sprintf('Mode motor stepper diubah ke %s (%s).', $selectedMode['name'], $selectedMode['desc']);
    $logStmt = $pdo->prepare(
        'INSERT INTO control_logs (device_id, change_type, heater_status, fan_status, trigger_temperature, description, created_at)
         VALUES (:id, :type, :heater, :fan, :temp, :desc, NOW())'
    );
    $logStmt->execute([
        ':id'     => $deviceId,
        ':type'   => 'STEPPER_MODE',
        ':heater' => $currentHeater,
        ':fan'    => $currentFan,
        ':temp'   => $currentTemp,
        ':desc'   => $desc,
    ]);

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Mode motor stepper berhasil diubah ke ' . $selectedMode['name'],
        'stepper_mode' => $modeInput,
        'mode_info'    => $selectedMode,
        'telemetry'    => [
            'temperature'   => $currentTemp,
            'heater_status' => $currentHeater,
            'fan_status'    => $currentFan,
        ]
    ], JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Gagal mengubah mode: ' . $e->getMessage()]);
}
