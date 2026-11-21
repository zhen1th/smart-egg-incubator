<?php
/**
 * REST API: Historical Telemetry and Activity Endpoint
 * Endpoint: GET /api/history.php
 * 
 * Provides sensor telemetry points for Chart.js rendering
 * and control activity logs for audit tables.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/database.php';

$deviceId = trim((string)($_GET['device_id'] ?? 'INCUBATOR_01'));
$range    = trim((string)($_GET['range'] ?? '1h'));
$type     = trim((string)($_GET['type'] ?? 'all')); // 'sensor', 'control', or 'all'
$limit    = isset($_GET['limit']) ? min(500, max(10, (int)$_GET['limit'])) : 100;

$pdo = getDbConnection();

// Map range to SQL interval
$intervalSql = match ($range) {
    '10m' => 'INTERVAL 10 MINUTE',
    '6h'  => 'INTERVAL 6 HOUR',
    '24h' => 'INTERVAL 24 HOUR',
    '7d'  => 'INTERVAL 7 DAY',
    default => 'INTERVAL 1 HOUR',
};

$response = [
    'success' => true,
    'range'   => $range,
];

// 1. Fetch sensor logs if requested
if ($type === 'all' || $type === 'sensor') {
    $sensorQuery = sprintf(
        'SELECT 
            id,
            temperature,
            humidity,
            recorded_at,
            DATE_FORMAT(recorded_at, "%%H:%%i:%%s") as time_label
         FROM sensor_logs
         WHERE device_id = :device_id 
           AND recorded_at >= DATE_SUB(NOW(), %s)
         ORDER BY recorded_at ASC
         LIMIT %d',
        $intervalSql,
        $limit
    );

    $sensorStmt = $pdo->prepare($sensorQuery);
    $sensorStmt->execute([':device_id' => $deviceId]);
    $sensorRows = $sensorStmt->fetchAll();

    $response['sensor_history'] = array_map(static function ($row) {
        return [
            'id'          => (int)$row['id'],
            'temperature' => (float)$row['temperature'],
            'humidity'    => (float)$row['humidity'],
            'recorded_at' => $row['recorded_at'],
            'time_label'  => $row['time_label'],
        ];
    }, $sensorRows);
}

// 2. Fetch control decision logs if requested
if ($type === 'all' || $type === 'control') {
    $controlStmt = $pdo->prepare(
        'SELECT 
            id,
            change_type,
            heater_status,
            fan_status,
            trigger_temperature,
            description,
            created_at,
            DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") as formatted_time
         FROM control_logs
         WHERE device_id = :device_id
         ORDER BY id DESC
         LIMIT :limit'
    );
    $controlStmt->bindValue(':device_id', $deviceId);
    $controlStmt->bindValue(':limit', 50, PDO::PARAM_INT);
    $controlStmt->execute();
    $controlRows = $controlStmt->fetchAll();

    $response['control_history'] = array_map(static function ($row) {
        return [
            'id'                  => (int)$row['id'],
            'change_type'         => $row['change_type'],
            'heater_status'       => (int)$row['heater_status'],
            'fan_status'          => (int)$row['fan_status'],
            'trigger_temperature' => $row['trigger_temperature'] !== null ? (float)$row['trigger_temperature'] : null,
            'description'         => $row['description'],
            'created_at'          => $row['created_at'],
            'formatted_time'      => $row['formatted_time'],
        ];
    }, $controlRows);
}

echo json_encode($response, JSON_UNESCAPED_SLASHES);

