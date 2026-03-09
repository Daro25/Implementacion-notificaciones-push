<?php
// Cargar configuración desde el JSON
$configData = json_decode(file_get_contents(__DIR__ . '/config.json'), true);

if (!$configData) {
    die("Error: No se pudo leer el archivo config.json");
}

// Configuración de la Base de Datos
$host = $configData['host'];
$db   = $configData['database'];
$user = $configData['user'];
$pass = $configData['password'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Exportar las llaves VAPID para usarlas en otros scripts
define('VAPID_PUBLIC', $configData['pub_key_vamp']);
define('VAPID_PRIVATE', $configData['priv_key_vamp']);
?>