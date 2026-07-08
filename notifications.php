<?php
// agent/notifications.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    // header('Location: ../auth/login.php');
    // exit();
}

require_once '../config/config.php';

// Pour les tests, si la session n'est pas définie, on utilise l'ID 8 (Ousmane Niang - Agent)
$agent_id = $_SESSION['user_id'] ?? 8; 
$error_msg = "";
$success_msg = "";

// Action facultative : Marquer toutes les notifications comme lues
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    try {
        $stmt_update = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE user_id = :user_id");
        $stmt_update->execute([':user_id' => $agent_id]);
        $success_msg = "Toutes les notifications ont été marquées comme lues.";
    } catch (PDOException $e) {
        $error_msg = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

try {
    // 1. Récupération de toutes les notifications de l'utilisateur connecté
    $query = "SELECT id, title, description, badge_text, badge_class, icon, icon_class, status, created_at 
              FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $agent_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Compteur dynamique des notifications non lues pour les badges de navigation
    $notif_count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = $agent_id AND status='unread'")->fetchColumn() ?: 0;

} catch (PDOException $e) {
    $error_msg = "Erreur de base de données : " . $e->getMessage();
    $notifications = [];
    $notif_count = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Centre de Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald: #00a651;
            --bg-body: #f8fafc;
            --sidebar-width: 280px;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
        }

        /* SIDEBAR (PC uniquement) */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            z-index: 100;
            padding: 24px;
        }
        .nav-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        .nav-menu-item:hover, .nav-menu-item.active {
            background-color: #f0fdf4;
            color: var(--emerald);
        }

        /* CONTENU CENTRAL */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            min-height: 100vh;
        }

        /* Top bar mobile */
        .top-bar-mobile {
            display: none;
            background: #fff;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .menu-toggle-btn {
            background: none;
            border: none;
            padding: 0;
            color: #1e293b;
        }

        /* CARTES DE NOTIFICATIONS */
        .notif-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            transition: all 0.2s ease;
            position: relative;
        }
        .notif-card.unread {
            background: #f8fafc;
            border-left: 4px solid var(--emerald);
        }
        .notif-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        
        /* Conteneurs d'icônes ronds et dynamiques */
        .icon-shape {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        /* Bottom Nav Mobile */
        .bottom-nav {
            display: none;
            background-color: #ffffff;
            border-top: 1px solid #f1f5f9;
            z-index: 1050;
        }
        .nav-link-custom {
            color: #94a3b8;
            font-size: 0.75rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .nav-link-custom.active {
            color: var(--emerald);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 991.98px) {
            .sidebar { display: none; }
            .main-content {
                margin-left: 0;
                padding: 20px;
                padding-bottom: 90px;
            }
            .top-bar-mobile {
                display: flex;
            }
            .bottom-nav {
                display: flex;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR ORDINATEUR -->
<div class="sidebar">
    <div class="d-flex align-items-center gap-2 mb-4 pb-3 border-bottom">
        <div class="rounded-circle bg-success px-2 py-1 text-white fw-bold">SS</div>
        <div>
            <h6 class="m-0 fw-bold text-dark">SENEGALSET</h6>
            <small class="text-muted" style="font-size: 10px;">Espace Opérations</small>
        </div>
    </div>

    <div class="mt-4">
        <a href="dashboard.php" class="nav-menu-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="interventions.php" class="nav-menu-item"><i class="bi bi-tools"></i> Interventions</a>
        <a href="carte.php" class="nav-menu-item"><i class="bi bi-map"></i> Carte Terrain</a>
        <a href="profil.php" class="nav-menu-item"><i class="bi bi-person"></i> Mon Profil</a>
    </div>
</div>

<!-- MENU COULISSANT MOBILE (Offcanvas) -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel" style="width: var(--sidebar-width);">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-success px-2 py-1 text-white fw-bold">SS</div>
            <div>
                <h6 class="m-0 fw-bold text-dark" id="mobileMenuLabel">SENEGALSET</h6>
                <small class="text-muted" style="font-size: 10px;">Espace Opérations</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-4">
        <a href="dashboard.php" class="nav-menu-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="interventions.php" class="nav-menu-item"><i class="bi bi-tools"></i> Interventions</a>
        <a href="carte.php" class="nav-menu-item"><i class="bi bi-map"></i> Carte Terrain</a>
        <a href="profil.php" class="nav-menu-item"><i class="bi bi-person"></i> Mon Profil</a>
    </div>
</div>

<!-- TOP BAR UNIQUE MOBILE -->
<div class="top-bar-mobile justify-content-between align-items-center">
    <button class="menu-toggle-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
        <i class="bi bi-list fs-3"></i>
    </button>
    <h6 class="m-0 fw-bold">Notifications</h6>
    <div class="position-relative">
        <i class="bi bi-bell-fill text-success fs-4"></i>
        <?php if($notif_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger" style="font-size: 0.55rem;"><?= $notif_count ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- ZONE DE CONTENU PRINCIPALE -->
<div class="main-content">
    <div class="container-fluid p-0">
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success rounded-3 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success_msg ?>
            </div>
        <?php endif; ?>

        <!-- EN-TÊTE DE PAGE -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold m-0 text-dark">Centre de notifications</h3>
                <p class="text-muted m-0">Restez informé des activités sur vos missions et signalements</p>
            </div>
            <?php if ($notif_count > 0): ?>
                <a href="notifications.php?action=read_all" class="btn btn-light border rounded-pill px-3 py-2 btn-sm fw-semibold text-dark">
                    <i class="bi bi-check2-all me-1"></i> Tout marquer comme lu
                </a>
            <?php endif; ?>
        </div>

        <!-- LISTE DES NOTIFICATIONS -->
        <div class="row">
            <div class="col-12 col-xl-9">
                <div class="d-flex flex-column gap-3">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach($notifications as $notif): ?>
                            <!-- Structure de la notification -->
                            <div class="notif-card <?= $notif['status'] === 'unread' ? 'unread' : '' ?>">
                                <div class="d-flex align-items-start gap-3">
                                    
                                    <!-- Icône dynamique basée sur la BDD -->
                                    <div class="icon-shape <?= htmlspecialchars($notif['icon_class'] ?? 'text-primary bg-primary-subtle') ?>">
                                        <i class="bi <?= htmlspecialchars($notif['icon'] ?? 'bi-bell-fill') ?>"></i>
                                    </div>
                                    
                                    <!-- Textes -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                            <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                                                <?= htmlspecialchars($notif['title']) ?>
                                                <?php if($notif['status'] === 'unread'): ?>
                                                    <span class="badge bg-success rounded-pill" style="font-size: 0.6rem; padding: 3px 6px;">Nouveau</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted text-nowrap" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i><?= date('d M Y à H:i', strtotime($notif['created_at'])) ?>
                                            </small>
                                        </div>
                                        <p class="text-secondary small mb-2"><?= htmlspecialchars($notif['description']) ?></p>
                                        
                                        <!-- Badge optionnel lié à l'entité (ex: "Signalement #12") -->
                                        <?php if (!empty($notif['badge_text'])): ?>
                                            <span class="badge <?= htmlspecialchars($notif['badge_class'] ?? 'bg-light text-primary border') ?> px-2 py-1" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($notif['badge_text']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- État vide (Aucune notification) -->
                        <div class="text-center py-5 bg-white rounded-4 border text-muted shadow-sm">
                            <div class="icon-shape bg-light text-muted mx-auto mb-3" style="border-radius: 50%; width: 64px; height: 64px; font-size: 2rem;">
                                <i class="bi bi-bell-slash"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Aucune notification</h5>
                            <p class="small text-muted px-3">Vous êtes à jour ! Toutes les nouvelles alertes s'afficheront ici.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BLOC ASTUCES / RÉSUMÉ (Optionnel - Version PC uniquement) -->
            <div class="col-12 col-xl-3 d-none d-xl-block">
                <div class="bg-white border rounded-4 p-4 sticky-top" style="top: 40px;">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Astuce Agent</h6>
                    <p class="small text-muted mb-0">
                        Pensez à activer les alertes SMS ou Push sur votre profil pour être informé en temps réel lors de l'affectation d'une urgence sur votre zone d'intervention.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- BARRE DE NAVIGATION MOBILE BASSE -->
<nav class="navbar bottom-nav fixed-bottom py-2 shadow-lg">
    <div class="container-fluid d-flex justify-content-around">
        <a href="dashboard.php" class="nav-link-custom"><i class="bi bi-grid mb-1 fs-5"></i>Dashboard</a>
        <a href="interventions.php" class="nav-link-custom"><i class="bi bi-tools mb-1 fs-5"></i>Missions</a>
        <a href="carte.php" class="nav-link-custom"><i class="bi bi-map mb-1 fs-5"></i>Carte</a>
        <a href="profil.php" class="nav-link-custom"><i class="bi bi-person mb-1 fs-5"></i>Profil</a>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>