<?php
// auth/send_reset_email.php
session_start();
require_once __DIR__ . '/../config/config.php'; 

$message_info = "";
$message_type = "info"; // pour la classe Bootstrap (success/danger)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expiry, $email]);

        $link = "http://localhost/SENEGALSET/auth/reset_password.php?token=" . $token;
        $message_info = "Un lien de réinitialisation a été généré : <a href='$link' class='alert-link'>Cliquez ici pour réinitialiser</a>";
        $message_type = "success";
    } else {
        $message_info = "Aucun compte trouvé avec cet email.";
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation - SENEGALSET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f6f9; font-family: 'Plus Jakarta Sans', sans-serif; }
        .auth-card { background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="auth-card">
        <h4 class="fw-bold mb-4">Récupération de compte</h4>
        
        <?php if ($message_info): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message_info ?></div>
            <a href="login.php" class="btn btn-secondary w-100">Retour à la connexion</a>
        <?php else: ?>
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">Entrez votre adresse email</label>
                    <input type="email" name="email" class="form-control" required placeholder="nom@exemple.com">
                </div>
                <button type="submit" class="btn btn-success w-100 fw-bold">Envoyer le lien</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>