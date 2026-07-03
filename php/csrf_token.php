<?php
require_once __DIR__ . '/session_boot.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['token' => csrf_token()]);
