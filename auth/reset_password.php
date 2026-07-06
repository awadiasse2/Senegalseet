<?php
// auth/reset_password.php
session_start();
require_once __DIR__ . '/../config/config.php'; 

$token = $_GET['token'] ?? '';
$error = "";

// Vérification du token en base de données
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Ce lien est invalide ou a expiré.";
    }
} else {
    $error = "Token manquant.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - SENEGALSET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f6f9; font-family: 'Plus Jakarta Sans', sans-serif; }
        .auth-card { background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="auth-card">
        <h4 class="fw-bold mb-4">Définir un nouveau mot de passe</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <a href="login.php" class="btn btn-secondary w-100">Retour à la connexion</a>
        <?php else: ?>
            <form action="update_password.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-success w-100 fw-bold">Changer le mot de passe</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>