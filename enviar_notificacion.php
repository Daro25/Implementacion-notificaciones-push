<?php
require 'vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = $_POST['mensaje'];
    
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:tu@correo.com',
            'publicKey' => 'TU_CLAVE_PUBLICA',
            'privateKey' => 'TU_CLAVE_PRIVADA',
        ],
    ];

    $webPush = new WebPush($auth);
    $payload = json_encode(['title' => 'Notificación Global', 'body' => $mensaje]);

    $pdo = new PDO('mysql:host=localhost;dbname=nombre_bd', 'usuario', 'password');
    $subscriptions = $pdo->query("SELECT * FROM push_subscriptions")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subscriptions as $row) {
        $sub = Subscription::create([
            'endpoint' => $row['endpoint'],
            'publicKey' => $row['p256dh'],
            'authToken' => $row['auth'],
        ]);
        $webPush->queueNotification($sub, $payload);
    }

    foreach ($webPush->flush() as $report) {
        if (!$report->isSuccess()) {
            // Limpieza: borrar tokens que ya no sirven
            $endpoint = $report->getRequest()->getUri()->__toString();
            $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")->execute([$endpoint]);
        }
    }
    echo "Enviado a todos.";
}
?>

<form method="POST">
    <input type="text" name="mensaje" placeholder="Escribe el mensaje" required>
    <button type="submit">Enviar a todos los logueados</button>
</form>