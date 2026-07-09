<?php
// config.php
$host = 'localhost';
$dbname = 'seneset';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

function write_log($level, $message) {
    $log_path = __DIR__ . '/../logs/system.log';
    $date = date('Y-m-d H:i:s');
    $formatted = "[{$date}] [" . strtoupper($level) . "] {$message}\n";
    file_put_contents($log_path, $formatted, FILE_APPEND);
}
?>