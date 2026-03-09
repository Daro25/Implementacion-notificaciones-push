<?php
require_once 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$mensaje_estado = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensaje'])) {
    $titulo = $_POST['titulo'] ?? 'Aviso de Marketplace';
    $cuerpo = $_POST['mensaje'];

    $auth = [
        'VAPID' => [
            'subject'   => 'mailto:admin@tu-dominio.com',
            'publicKey' => VAPID_PUBLIC,
            'privateKey' => VAPID_PRIVATE,
        ],
    ];

    $webPush = new WebPush($auth);
    $payload = json_encode(['title' => $titulo, 'body' => $cuerpo]);

    // Obtener todos los suscritos
    $stmt = $pdo->query("SELECT * FROM push_subscriptions");
    $subs = $stmt->fetchAll();

    foreach ($subs as $row) {
        $subscription = Subscription::create([
            'endpoint'  => $row['endpoint'],
            'publicKey' => $row['p256dh'],
            'authToken' => $row['auth'],
        ]);
        $webPush->queueNotification($subscription, $payload);
    }

    foreach ($webPush->flush() as $report) {
        if (!$report->isSuccess()) {
            // Limpieza automática de tokens inválidos
            $endpoint = $report->getRequest()->getUri()->__toString();
            $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
            $del->execute([$endpoint]);
        }
    }
    $mensaje_estado = "✅ Notificación enviada a " . count($subs) . " dispositivos.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Notificaciones</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        form { max-width: 400px; border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        input, textarea, button { width: 100%; margin-bottom: 10px; padding: 8px; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Enviar Notificación Push a todos</h2>
    
    <?php if($mensaje_estado) echo "<p class='success'>$mensaje_estado</p>"; ?>

    <form method="POST">
        <input type="text" name="titulo" placeholder="Título de la notificación" required>
        <textarea name="mensaje" placeholder="Escribe el cuerpo del mensaje..." required></textarea>
        <button type="submit">Enviar a todos los usuarios</button>
    </form>

    <hr>
    <button onclick="subscribeUser()">🔔 Activar notificaciones en este navegador</button>

    <script>
        const VAPID_PUBLIC_KEY = "<?php echo VAPID_PUBLIC; ?>";
    </script>
    <script src="main.js"></script>
</body>
</html>