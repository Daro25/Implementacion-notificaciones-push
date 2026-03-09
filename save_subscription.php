<?php
require_once 'db.php';
session_start();

// En un entorno real, aquí usarías el ID real del usuario logueado
$user_id = $_SESSION['user_id'] ?? 1; 

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['endpoint'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)");
        
        $stmt->execute([
            $user_id,
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth']
        ]);
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Datos de suscripción no proporcionados"]);
}
?>