<?php
// 1. Evitar que PHP imprima errores/avisos en el cuerpo de la respuesta
ini_set('display_errors', 0); 
error_reporting(E_ALL); // Los errores se siguen registrando internamente, pero no se "escupen" al navegador

header('Content-Type: application/json');
require_once 'db.php';
session_start();
$modo_pruebas = true;
// En un entorno real, aquí usarías el ID real del usuario logueado
// Obtener token de la cookie
    $token = $_COOKIE['token'] ?? null;
    $user_id = null;
    if (!$token && !$modo_pruebas) {
        echo json_encode(['success' => false, 'error' => 'Sesión no encontrada o expirada']);
        exit();
    }else if(!$modo_pruebas){
        // Desencriptar token
        $private_key = file_get_contents('private_key.pem');
        $encryptedData = base64_decode($token);
        $decrypted_data = '';

        if (openssl_private_decrypt($encryptedData, $decrypted_data, $private_key)) {
            $disTokenJSON = json_decode($decrypted_data, true);
            if (!$disTokenJSON) {
                echo json_encode(['success' => false, 'error' => 'Token corrupto']);
                exit();
            }

            $tokenExpire = $disTokenJSON['expiracion'];
            $timeActual = date("Y-m-d H:i:s");

            if (strtotime($tokenExpire) < strtotime($timeActual)) {
                setcookie("token", "", time() - 3600, "/");
                echo json_encode(['success' => false, 'error' => 'El token ya expiró, vuelve a iniciar sesión.']);
                exit();
            }

            // ✅ Usuario válido
            $user_id = (int)$disTokenJSON['id'];

        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo leer la identidad del token']);
            exit();
        }
    }
$user_id = $user_id ?? 1; // En un entorno real, el ID vendría del token
$data = json_decode(file_get_contents('php://input'), true);
if($data['endpoint'] === 'undefined') {
    echo json_encode(["status" => "error", "message" => "Endpoint no válido"]);
    exit();
}
if (isset($data['endpoint'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO push_subscriptions2 (user_id, endpoint, p256dh, auth) 
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