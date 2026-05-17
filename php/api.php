<?php
require_once __DIR__ . '/db_connect.php';
$action = $_GET['action'] ?? 'location';
header('Content-Type: application/json; charset=utf-8');
if ($action === 'location') {
    $stmt = $pdo->query('SELECT id, name, description, latitude, longitude FROM locations ORDER BY id DESC LIMIT 20');
    $locations = $stmt->fetchAll();
    if (empty($locations)) {
        $locations = [
            ['id' => 1, 'name' => 'QuickTrend Cafe', 'description' => 'みんなで集まる人気スポット', 'latitude' => 35.6895, 'longitude' => 139.6917],
            ['id' => 2, 'name' => 'リラックス公園', 'description' => '自然を楽しめる場所です', 'latitude' => 35.6890, 'longitude' => 139.6920],
        ];
    }
    echo json_encode(['locations' => $locations]);
    exit;
}
http_response_code(400);
echo json_encode(['error' => 'invalid_action']);
