<?php
session_start();
require_once __DIR__ . '/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sighup.html');
    exit;
}
$name = trim($_POST['name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$genre = trim($_POST['genre'] ?? '');
if (!$name || !$email || !$password || !$genre) {
    header('Location: sighup.html');
    exit;
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    header('Location: sighup.html');
    exit;
}
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, genre, created_at) VALUES (:name, :email, :password_hash, :genre, NOW())');
$insert->execute([ 'name' => $name, 'email' => $email, 'password_hash' => $passwordHash, 'genre' => $genre ]);
$userId = $pdo->lastInsertId();
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['user_genre'] = $genre;
header('Location: QuickTrend.php');
