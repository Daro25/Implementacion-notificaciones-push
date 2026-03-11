<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// 1. Leer el archivo config.json
$jsonString = file_get_contents('config.json');
$configData = json_decode($jsonString, true);

// Asignar variables de BD y VAPID
$user = $configData["user"];
$server = $configData["host"];
$database = $configData["database"];
$password = $configData["password"];

$pubKeyVamp = $configData["pub_key_vamp"];
$privKeyVamp = $configData["priv_key_vamp"];

// Conexión con mysqli (siguiendo tu estilo)
$conex = mysqli_connect($server, $user, $password, $database);

if (!$conex) {
    echo json_encode(['success' => false, 'error' => 'Conexión fallida: ' . mysqli_connect_error()]);
    exit();
}

try {
    // 2. Recibir el cuerpo de la petición JSON
    $jsonRecibido = file_get_contents('php://input');
    $requestData = json_decode($jsonRecibido, true);

    // Extraer datos
    $titulo  = $requestData['titulo'] ?? 'Aviso de Marketplace';
    $mensaje = $requestData['mensaje'] ?? '';
    $icon    = $requestData['icon'] ?? 'https://cdn-icons-png.flaticon.com/512/1827/1827347.png';
    $url     = $requestData['url'] ?? 'http://localhost/Implementacion-notificaciones-push/';

    // Validar que el mensaje no esté vacío
    if (empty($mensaje)) {
        echo json_encode(['success' => false, 'error' => 'El contenido del mensaje es requerido.']);
        exit;
    }

    // 3. Preparar el Payload y validar tamaño (Evitar el error de 4078 octetos)
    $payloadData = [
        'title' => $titulo,
        'body'  => $mensaje,
        'icon'  => $icon,
        'url'   => $url
    ];
    $payloadString = json_encode($payloadData);

    if (mb_strlen($payloadString, '8bit') > 3900) {
        echo json_encode(['success' => false, 'error' => 'El payload excede el tamaño permitido por los navegadores.']);
        exit;
    }

    // 4. Configurar WebPush
    $auth = [
        'VAPID' => [
            'subject'    => 'mailto:admin@tu-dominio.com',
            'publicKey'  => $pubKeyVamp,
            'privateKey' => $privKeyVamp,
        ],
    ];

    $webPush = new WebPush($auth);

    // 5. Obtener suscriptores de la BD
    $query = "SELECT endpoint, p256dh, auth FROM push_subscriptions";
    $result = mysqli_query($conex, $query);

    $totalEnviados = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $subscription = Subscription::create([
            'endpoint'  => $row['endpoint'],
            'publicKey' => $row['p256dh'],
            'authToken' => $row['auth'],
        ]);

        $webPush->queueNotification($subscription, $payloadString);
        $totalEnviados++;
    }

    // 6. Ejecutar envío y limpiar tokens fallidos
    $enviadosConExito = 0;
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        
        if ($report->isSuccess()) {
            $enviadosConExito++;
        } else {
            // Si falla (token expirado o revocado), lo borramos
            $stmtDel = $conex->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
            $stmtDel->bind_param("s", $endpoint);
            $stmtDel->execute();
            $stmtDel->close();
        }
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Proceso de envío completado',
        'detalles' => [
            'total_intentados' => $totalEnviados,
            'entregados_exitosamente' => $enviadosConExito
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
} finally {
    mysqli_close($conex);
}
?>