<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - SENEGALSET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f3f6f9; font-family: 'Plus Jakarta Sans', sans-serif; }
        /* La même structure que vos pages précédentes */
        .auth-card { background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="auth-card">
        <h4 class="fw-bold mb-3">Réinitialisation</h4>
        <p class="text-muted mb-4" style="font-size: 0.9rem;">Entrez votre adresse e-mail pour recevoir le lien de récupération.</p>
        
        <form action="send_reset_email.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Votre adresse email</label>
                <input type="email" name="email" class="form-control" required placeholder="nom@exemple.com">
            </div>
            <button type="submit" class="btn btn-success w-100 fw-bold">Envoyer le lien</button>
            <div class="mt-3 text-center">
                <a href="login.php" class="text-decoration-none text-muted" style="font-size: 0.8rem;">Retour à la connexion</a>
            </div>
        </form>
    </div>

</body>
</html>