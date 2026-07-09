<?php
// client/profil.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

try {
    // 1. RÉCUPÉRATION DE L'UTILISATEUR CONNECTÉ
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../auth/logout.php');
        exit();
    }

    // 2. STATISTIQUES DYNAMIQUES
    // Total des signalements créés par ce citoyen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_reports = $stmt->fetchColumn();

    // Signalements résolus (Statut exact : 'Résolu')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'Résolu'");
    $stmt->execute([$user_id]);
    $resolved_reports = $stmt->fetchColumn();

    // Signalements actuellement pris en charge (Statut exact : 'En cours')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'En cours'");
    $stmt->execute([$user_id]);
    $progress_reports = $stmt->fetchColumn();

    // 3. COMPTEUR DE NOTIFICATIONS NON LUES
    $unread_notifications = 0; 
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
        $stmt->execute([$user_id]);
        $unread_notifications = $stmt->fetchColumn();
    } catch (Exception $e) { 
        // Fallback si la colonne s'appelle différemment
    }

} catch (PDOException $e) {
    $error_msg = "Erreur de base de données : " . $e->getMessage();
}

// Gestion de l'avatar de profil
$avatar_url = (!empty($user['avatar']) && file_exists("../uploads/avatars/" . $user['avatar'])) 
    ? "../uploads/avatars/" . htmlspecialchars($user['avatar']) 
    : "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2 family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald: #00a651;
            --bg-body: #f8fafc;
            --text-dark: #0f172a;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #475569;
            font-size: 0.85rem;
            overflow-x: hidden;
            padding-bottom: 80px; /* Évite la superposition avec la barre mobile */
        }
        @media (min-width: 768px) {
            body { padding-bottom: 30px; }
        }
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.015);
            padding: 20px;
            margin-bottom: 20px;
            word-wrap: break-word;
        }
        .avatar-container {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto;
        }
        .avatar-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
        }
        .avatar-edit-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--emerald);
            color: white;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }
        .badge-verified {
            background-color: #d1fae5;
            color: #065f46;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        .stat-card {
            border-radius: 14px;
            border: 1px solid #f1f5f9;
            padding: 12px 6px;
            background: #fff;
            text-align: center;
            height: 100%;
        }
        .stat-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 6px;
            font-size: 1rem;
        }
        .info-row {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 10px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-value {
            text-align: right;
            max-width: 65%;
            word-break: break-all;
        }
        @media (max-width: 480px) {
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
            }
            .info-value {
                text-align: left;
                max-width: 100%;
            }
        }
        .form-switch .form-check-input:checked {
            background-color: var(--emerald);
            border-color: var(--emerald);
        }
        .assistance-card {
            background: linear-gradient(135deg, #00a651 0%, #1e3a8a 100%);
            color: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-custom-outline {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            font-weight: 500;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.8rem;
        }
        
        /* Menu de navigation mobile amélioré */
        .bottom-nav {
            background-color: #ffffff;
            border-top: 1px solid #edeef0;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.03);
            z-index: 1030;
        }
        .nav-link-custom {
            color: #94a3b8;
            font-size: 11px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-weight: 500;
            padding: 4px 0;
        }
        .nav-link-custom i {
            font-size: 1.25rem;
            margin-bottom: 2px;
        }
        .nav-link-custom.active {
            color: var(--emerald);
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container-xl px-3 py-3" style="max-width: 800px;">
    <div class="mb-3">
        <a href="espace_client.php" class="text-decoration-none text-dark d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left-circle-fill fs-5 text-secondary"></i> <span class="fw-medium">Retour à l'accueil</span>
        </a>
    </div>
    
    <div class="row align-items-center mb-4 text-center text-sm-start g-2 pb-3 border-bottom">
        <div class="col-12 col-sm-auto">
            <div class="d-flex justify-content-center justify-content-sm-start align-items-center gap-2">
                <div class="rounded-circle bg-success px-2 py-1 text-white fw-bold" style="font-size:13px;">SS</div>
                <h5 class="m-0 fw-bold text-dark" style="letter-spacing: 0.02em;">SENEGALSET</h5>
            </div>
        </div>
        <div class="col-12 col-sm-auto me-sm-auto">
            <span class="text-muted d-block" style="font-size: 9px;">— Pour des villes plus propres et connectées —</span>
        </div>
    </div>

    <div class="mb-4 text-center text-sm-start">
        <h4 class="fw-bold m-0 text-dark">Mon Profil</h4>
        <small class="text-muted">Espace de gestion de votre compte citoyen</small>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger rounded-3" role="alert"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="card-custom">
        <div class="row align-items-center justify-content-center g-3 text-center text-sm-start">
            <div class="col-12 col-sm-auto">
                <div class="avatar-container">
                    <img src="<?= $avatar_url ?>" class="avatar-img" alt="Avatar">
                    <div class="avatar-edit-badge"><i class="bi bi-camera-fill" style="font-size: 11px;"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm flex-sm-grow-1">
                <div class="d-flex align-items-center justify-content-center justify-content-sm-start gap-2 mb-2 flex-wrap">
                    <h5 class="fw-bold m-0 text-dark" style="font-size: 1.2rem;"><?= htmlspecialchars($user['name'] ?? 'Citoyen') ?></h5>
                    <span class="badge badge-verified"><i class="bi bi-patch-check-fill me-1"></i>Citoyen Vérifié</span>
                </div>
                <div class="row g-2 text-muted justify-content-center justify-content-sm-start" style="font-size: 0.8rem;">
                    <div class="col-12 text-sm-start text-truncate"><i class="bi bi-envelope me-2 text-success"></i><?= htmlspecialchars($user['email'] ?? 'Non renseigné') ?></div>
                    <div class="col-12 text-sm-start"><i class="bi bi-telephone me-2 text-success"></i><?= htmlspecialchars($user['phone'] ?? '+221 -- --- -- --') ?></div>
                </div>
            </div>
            <div class="col-12 col-sm-auto mt-3 mt-sm-0">
                <a href="edit_profile.php" class="btn btn-success fw-semibold px-4 py-2 rounded-3 w-100 d-inline-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-pencil-fill"></i> Modifier
                </a>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-megaphone"></i></div>
                <div class="text-muted mb-1" style="font-size: 10px;">Signalements</div>
                <h5 class="fw-bold m-0 text-dark"><?= $total_reports ?></h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
                <div class="text-muted mb-1" style="font-size: 10px;">Résolus</div>
                <h5 class="fw-bold m-0 text-dark"><?= $resolved_reports ?></h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock-history"></i></div>
                <div class="text-muted mb-1" style="font-size: 10px;">En cours</div>
                <h5 class="fw-bold m-0 text-dark"><?= $progress_reports ?></h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-bell"></i></div>
                <div class="text-muted mb-1" style="font-size: 10px;">Notifications</div>
                <h5 class="fw-bold m-0 text-dark"><?= $unread_notifications ?></h5>
            </div>
        </div>
    </div>

    <div class="row g-3">
        
        <div class="col-12 col-md-6">
            <div class="card-custom h-100 mb-0">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-vcard me-2 text-success"></i>Informations personnelles</h6>
                <div>
                    <div class="info-row"><span class="text-muted">Nom complet</span><span class="info-value fw-semibold text-dark"><?= htmlspecialchars($user['name'] ?? '-') ?></span></div>
                    <div class="info-row"><span class="text-muted">Email</span><span class="info-value text-dark"><?= htmlspecialchars($user['email'] ?? '-') ?></span></div>
                    <div class="info-row"><span class="text-muted">Téléphone</span><span class="info-value text-dark"><?= htmlspecialchars($user['phone'] ?? '-') ?></span></div>
                </div>
                <a href="edit_profile.php" class="btn btn-custom-outline w-100 mt-3 text-decoration-none text-center"><i class="bi bi-pencil me-1"></i> Modifier les détails</a>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card-custom mb-3">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock me-2 text-success"></i>Sécurité</h6>
                <div>
                    <a href="#" class="info-row text-decoration-none text-dark"><span><i class="bi bi-lock me-2 text-muted"></i>Mot de passe</span><i class="bi bi-chevron-right text-muted"></i></a>
                    <div class="info-row"><span><i class="bi bi-shield-check me-2 text-muted"></i>Double facteur (2FA)</span><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" checked></div></div>
                </div>
            </div>

            <div class="card-custom mb-0">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-2 text-success"></i>Préférences</h6>
                <div>
                    <div class="info-row"><span><i class="bi bi-bell me-2 text-muted"></i>Notifications push</span><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" checked></div></div>
                    <div class="info-row border-bottom-0 pb-0"><span><i class="bi bi-envelope me-2 text-muted"></i>Notifications email</span><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" checked></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="assistance-card mt-3 d-flex flex-column flex-sm-row align-items-center justify-content-between text-center text-sm-start gap-3">
        <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
            <div class="fs-3 text-white bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-headset"></i></div>
            <div>
                <h6 class="fw-bold m-0">Besoin d'aide ?</h6>
                <small class="text-white-50" style="font-size:11px;">Notre équipe support est à votre écoute.</small>
            </div>
        </div>
        <a href="support.php" class="btn btn-light btn-sm fw-semibold text-dark px-3 py-2 rounded-3 text-decoration-none w-100 w-sm-auto" style="font-size:11px;">Support <i class="bi bi-chevron-right ms-1"></i></a>
    </div>

    <div class="my-4">
        <a href="../auth/logout.php" class="btn btn-danger w-100 py-2.5 fw-semibold rounded-3 d-flex align-items-center justify-content-center gap-2" onclick="return confirm('Voulez-vous vous déconnecter ?');">
            <i class="bi bi-box-arrow-left"></i> Déconnexion
        </a>
    </div>

</div>

<nav class="navbar bottom-nav fixed-bottom py-2 d-md-none">
    <div class="container-fluid d-flex justify-content-around">
        <a href="espace_client.php" class="nav-link-custom"><i class="bi bi-house"></i>Accueil</a>
        <a href="signaler.php" class="nav-link-custom"><i class="bi bi-megaphone"></i>Signaler</a>
        <a href="carte.php" class="nav-link-custom"><i class="bi bi-map"></i>Carte</a>
        <a href="notifications.php" class="nav-link-custom position-relative">
            <i class="bi bi-bell"></i>
            <?php if ($unread_notifications > 0): ?>
                <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger" style="font-size:8px; margin-left: 8px;">
                    <?= $unread_notifications ?>
                </span>
            <?php endif; ?>
            Notifs
        </a>
        <a href="profil.php" class="nav-link-custom active"><i class="bi bi-person-fill"></i>Profil</a>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>