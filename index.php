<?php
// index.php
session_start();
$is_connected = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SenegalSet — Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #3ca336;
            --dark-green: #007f47;
            --brand-blue: #0b4ca0;
        }

        body, html { margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; background-color: #ffffff; overflow-x: hidden; }

        /* HERO BANNER RESPONSIVE */
        .hero-banner { position: relative; width: 100%; height: 300px; background: url('./assets/img/arrier.png') center/cover no-repeat; }
        @media (min-width: 768px) { .hero-banner { height: 420px; } }

        .logo-corner-badge { position: absolute; top: 0; left: 0; width: 150px; height: 120px; background-color: var(--dark-green); border-bottom-right-radius: 100%; display: flex; align-items: center; justify-content: center; z-index: 10; }
        @media (min-width: 768px) { .logo-corner-badge { width: 220px; height: 180px; } }

        .logo-circle { background-color: #ffffff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        @media (min-width: 768px) { .logo-circle { width: 110px; height: 110px; } }

        /* TEXTES ET BOUTONS RESPONSIVE */
        .main-brand-title { font-size: 28px; font-weight: 800; color: var(--brand-blue); text-transform: uppercase; margin-bottom: 8px; }
        @media (min-width: 768px) { .main-brand-title { font-size: 38px; } }

        .btn-red-submit, .btn-green-outline { display: block; width: 100%; margin: 10px 0; text-align: center; padding: 12px; border-radius: 6px; font-weight: 700; text-decoration: none; }
        @media (min-width: 768px) { .btn-red-submit, .btn-green-outline { display: inline-block; width: auto; margin-right: 16px; } }

        /* SECTIONS RESPONSIVE */
        .why-blue-box { padding: 40px 20px; }
        @media (min-width: 768px) { .why-blue-box { padding: 60px 50px; } }

        .why-image-box { min-height: 250px; background: url('./assets/img/AW.jfif') center/cover no-repeat; }
        @media (min-width: 768px) { .why-image-box { min-height: 350px; } }

        .icon-step-holder { width: 80px; height: 80px; margin: 0 auto 16px auto; }
        @media (min-width: 768px) { .icon-step-holder { width: 120px; height: 120px; } }
    </style>
</head>
<body>

    <header class="hero-banner">
        <div class="logo-corner-badge">
            <div class="logo-circle">
                <img src="assets/img/logo.png" style="width:10%;" alt="SenegalSet">
            </div>
        </div>
    </header>

    <main class="py-5 container">
        <h1 class="main-brand-title">SENEGAL<span>SET</span></h1>
        <div style="background-color: var(--primary-green); color: #fff; padding: 10px; display: inline-block; border-radius: 4px; margin-bottom: 20px;">
            Plateforme Nationale de Gestion des Déchets et des Interventions Urbaines
        </div>
        <p style="font-size: 18px; color: var(--brand-blue);">Signalez les problèmes urbains, suivez les interventions en temps réel.</p>
        
        <div class="mt-4">
            <a href="auth/login.php" class="btn-red-submit" style="background: #e52313; color: white;">Signaler un problème</a>
            <a href="auth/register.php" class="btn-green-outline" style="border: 2px solid var(--primary-green); color: var(--primary-green);">Créer un compte</a>
            <a href="auth/login.php" class="btn-green-outline" style="border: 2px solid var(--primary-green); color: var(--primary-green);">Accéder à mon espace</a>
        </div>
    </main>

    <section class="container-fluid p-0" style="background-color: var(--brand-blue); color: #ffffff;">
        <div class="row g-0">
            <div class="col-12 col-md-6 why-blue-box">
                <h2>Pourquoi SENEGALSET ?</h2>
                <ul>
                    <li>Signalement rapide</li>
                    <li>Intervention efficace</li>
                    <li>Suivi en temps réel</li>
                </ul>
            </div>
            <div class="col-12 col-md-6 why-image-box"></div>
        </div>
    </section>

    <section class="py-5 text-center">
        <div class="container">
            <h2 class="mb-5" style="color: var(--primary-green);">Comment ça fonctionne ?</h2>
            <div class="row">
                <?php $steps = ['Signaler', 'Geolocaliser', 'Intervention', 'Notification'];
                foreach($steps as $step): ?>
                <div class="col-6 col-md-3">
                    <div class="icon-step-holder">
                        <img src="./assets/img/<?= strtolower($step) ?>.svg" class="img-fluid" alt="<?= $step ?>">
                    </div>
                    <p class="fw-bold"><?= $step ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>