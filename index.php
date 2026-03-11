<?php
require_once 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$mensaje_estado = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensaje'])) {
    $titulo = $_POST['titulo'] ?? 'Aviso de Marketplace';
    $cuerpo = $_POST['mensaje'];
    
    // --- LÓGICA DE CAMPOS OPCIONALES ---
    // Si el campo está vacío, usamos una imagen y link por defecto.
    $icon = !empty($_POST['icon']) ? $_POST['icon'] : 'https://cdn-icons-png.flaticon.com/512/1827/1827347.png';
    $url  = !empty($_POST['url']) ? $_POST['url'] : 'http://localhost/Implementacion-notificaciones-push/';

    $auth = [
        'VAPID' => [
            'subject'    => 'mailto:admin@tu-dominio.com',
            'publicKey'  => VAPID_PUBLIC,
            'privateKey' => VAPID_PRIVATE,
        ],
    ];

    $webPush = new WebPush($auth);
    
    $payload = json_encode([
        'title' => $titulo, 
        'body'  => $cuerpo,
        'icon'  => $icon,
        'url'   => $url
    ]);

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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 500px; margin: auto; background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #1a73e8; text-align: center; }
        label { font-size: 0.85rem; font-weight: 600; color: #666; display: block; margin-bottom: 5px; }
        input, textarea, button { width: 100%; margin-bottom: 20px; padding: 12px; border-radius: 8px; border: 1px solid #ddd; box-sizing: border-box; }
        textarea { height: 80px; resize: none; }
        .optional { font-style: italic; color: #999; font-weight: normal; }
        button { background-color: #1a73e8; color: white; border: none; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        button:hover { background-color: #1557b0; }
        .btn-subscribe { background-color: #34a853; margin-top: 10px; }
        .btn-subscribe:hover { background-color: #2d8e47; }
        .success { background: #e6f4ea; color: #1e7e34; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

<div class="container">
    <h2>📣 Centro de Mensajes</h2>
    
    <?php if($mensaje_estado) echo "<div class='success'>$mensaje_estado</div>"; ?>

    <form method="POST">
        <label>Título del mensaje:</label>
        <input type="text" name="titulo" placeholder="Ej: ¡Nuevo mensaje!" required>
        
        <label>Contenido:</label>
        <textarea name="mensaje" placeholder="¿Qué quieres decirles a todos?" required></textarea>
        
        <label>URL del Icono <span class="optional">(Opcional)</span>:</label>
        <input type="url" name="icon" placeholder="https://sitio.com/logo.png">
        
        <label>Enlace de destino <span class="optional">(Opcional)</span>:</label>
        <input type="url" name="url" placeholder="https://tuweb.com/oferta">
        
        <button type="submit">🚀 Lanzar Notificación</button>
    </form>

    <button onclick="subscribeUser()" class="btn-subscribe">🔔 Probar suscripción en este equipo</button>
</div>

<script>
    const VAPID_PUBLIC_KEY = "<?php echo VAPID_PUBLIC; ?>";
</script>
<script src="main.js"></script>
</body>
</html>