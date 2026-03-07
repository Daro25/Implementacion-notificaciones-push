<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null; 
if (!$user_id) exit;

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['endpoint'])) {
    $pdo = new PDO('mysql:host=localhost;dbname=nombre_bd', 'usuario', 'password');
    $stmt = $pdo->prepare("INSERT IGNORE INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $user_id, 
        $data['endpoint'], 
        $data['keys']['p256dh'], 
        $data['keys']['auth']
    ]);
}