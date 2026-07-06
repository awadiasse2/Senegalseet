<?php
session_start();

// SÉCURITÉ : Bloquer l'accès si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// SÉCURITÉ : Bloquer l'accès si l'utilisateur n'est pas admin
$user_role = trim(strtolower($_SESSION['user_role'] ?? ''));
if ($user_role !== 'admin') {
    header('Location: ../Client/index.php');
    exit();
}

require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Carte</title>
</head>
<body>
    <p>Page Carte - Accès Admin uniquement</p>
</body>
</html>
