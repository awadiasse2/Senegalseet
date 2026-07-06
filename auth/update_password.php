<?php
// auth/update_password.php
session_start();
require_once __DIR__ . '/../config/config.php'; 

$message = "";
$status = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // 1. Validation : Vérifier si le token est encore valide
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Hashage et mise à jour
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->execute([$hashed_password, $token]);
        
        $message = "Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter.";
    } else {
        $message = "Erreur : Ce lien est invalide ou a expiré.";
        $status = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Succès - SENEGALSET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f6f9; font-family: 'Plus Jakarta Sans', sans-serif; }
        .auth-card { background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="auth-card text-center">
        <div class="mb-3">
            <i class="bi <?= ($status == 'success') ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?>" style="font-size: 3rem;"></i>
        </div>
        <h4 class="fw-bold mb-3"><?= ($status == 'success') ? 'Félicitations' : 'Erreur' ?></h4>
        <p class="text-muted"><?= $message ?></p>
        <a href="login.php" class="btn btn-primary w-100 mt-3 fw-bold">Retour à la connexion</a>
    </div>

</body>
</html>