<?php
// admin/Dashboard.php
session_start();

// SÉCURITÉ : Connexion et rôle Admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';
$user_id = $_SESSION['user_id'];

// RÉCUPÉRATION DU PROFIL ET DE L'AVATAR EN TEMPS RÉEL DANS LA BDD
try {
    $stmt_user = $pdo->prepare("SELECT name, role, avatar FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_user = null;
}

// Vérification stricte du rôle d'administrateur
$user_role = trim(strtolower($current_user['role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? ''));
if ($user_role !== 'admin') {
    header('Location: ../client/espace_client.php');
    exit();
}

// Variables Administrateur
$admin_name = !empty($current_user['name']) ? $current_user['name'] : 'Admin Sénégalset';
$default_avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80';

// Gestion dynamique et sécurisée du chemin de l'avatar
if (!empty($current_user['avatar'])) {
    if (strpos($current_user['avatar'], 'http') === 0) {
        $admin_avatar = $current_user['avatar'];
    } else {
        // Si l'avatar est stocké dans 'uploads/avatars/' au niveau racine
        $admin_avatar = '../uploads/avatars/' . ltrim($current_user['avatar'], '/');
        
        // Si le fichier n'existe pas dans le dossier uploads, on regarde à la racine standard
        if (!file_exists($admin_avatar)) {
            $admin_avatar = '../' . ltrim($current_user['avatar'], '/');
        }
        
        // Si toujours introuvable sur le disque, fallback sur l'image par défaut
        if (!file_exists($admin_avatar)) {
            $admin_avatar = $default_avatar;
        }
    }
} else {
    $admin_avatar = $default_avatar;
}

// STATISTIQUES
try {
    $counts = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'En attente' THEN 1 ELSE 0 END) as attente,
        SUM(CASE WHEN status = 'En cours' THEN 1 ELSE 0 END) as encours,
        SUM(CASE WHEN status = 'Résolu' THEN 1 ELSE 0 END) as resolu,
        SUM(CASE WHEN status = 'Rejeté' THEN 1 ELSE 0 END) as rejete
    FROM reports")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $counts = []; }

$total_reports   = (int)($counts['total'] ?? 0);
$attente_reports = (int)($counts['attente'] ?? 0);
$encours_reports = (int)($counts['encours'] ?? 0);
$resolu_reports  = (int)($counts['resolu'] ?? 0);
$rejete_reports  = (int)($counts['rejete'] ?? 0);

try {
    $total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (PDOException $e) { $total_users = 0; }

// Catégories
try {
    $categories_dist = $pdo->query("
        SELECT c.name, COUNT(r.id) as count_cat
        FROM categories c
        LEFT JOIN reports r ON r.category_id = c.id
        GROUP BY c.id
        ORDER BY count_cat DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories_dist = []; }

// Municipalités / Zones
try {
    $zones_dist = $pdo->query("
        SELECT z.name, COUNT(r.id) as count_zone
        FROM zones z
        LEFT JOIN reports r ON r.zone_id = z.id
        GROUP BY z.id
        ORDER BY count_zone DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $zones_dist = []; }

// Notifications récentes
try {
    $notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5")
        ->fetchAll(PDO::FETCH_ASSOC);
    $total_notifications = count($notifications);
} catch (PDOException $e) { 
    $notifications = []; 
    $total_notifications = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Dashboard Administrateur</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --sidebar: #06152d;
        --bg: #f5f7fb;
        --card: #ffffff;
        --text: #0f172a;
        --muted: #64748b;
        --green: #00a651;
        --blue: #3b82f6;
        --orange: #f59e0b;
        --red: #ef4444;
        --purple: #8b5cf6;
        --shadow: 0 10px 25px rgba(2, 6, 23, .06);
        --border: rgba(2, 6, 23, .06);
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        font-size: 0.82rem;
        overflow-x: hidden;
    }

    /* Sidebar Fixe Bureau */
    .sidebar-desktop {
        width: 270px;
        height: 100vh;
        background: var(--sidebar);
        padding: 18px 14px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 100;
        overflow-y: auto;
    }

    .brand-logo {
        width: 28px; height: 28px;
        border-radius: 10px;
        background: rgba(0,166,81,.15);
        display: flex; align-items: center; justify-content: center;
        color: var(--green);
        font-weight: 800;
    }

    /* Styles partagés pour les menus de navigation */
    .sidebar-nav .nav-link {
        color: rgba(148,163,184,.95);
        padding: 10px 12px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        transition: .15s ease;
    }
    .sidebar-nav .nav-link:hover {
        color: #fff;
        background: rgba(0,166,81,.18);
    }
    .sidebar-nav .nav-link.active {
        color: #fff;
        background: rgba(0,166,81,.22);
    }

    .sidebar-title {
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: rgba(148,163,184,.85);
        margin: 18px 10px 10px;
    }

    .help-box {
        background: rgba(0, 166, 81, 0.15);
        border: 1px dashed rgba(0,166,81,.7);
        border-radius: 14px;
        padding: 12px;
    }

    /* Structure principale */
    main {
        margin-left: 270px;
        min-width: 0;
        width: 100%;
    }

    /* Top Bar Mobile */
    .top-bar-mobile {
        display: none;
        background: var(--sidebar);
        padding: 12px 16px;
    }

    .card-soft {
        background: var(--card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        border-radius: 16px;
    }

    /* KPI */
    .kpi {
        padding: 16px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        min-height: 105px;
    }
    .kpi .kpi-left small {
        color: var(--muted);
        font-weight: 600;
        font-size: 0.72rem;
    }
    .kpi .kpi-left h5 {
        font-size: 1.2rem;
        margin: 4px 0 0 0;
        font-weight: 900;
    }
    .kpi .kpi-icon {
        width: 38px; height: 38px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        background: rgba(0,166,81,.12);
        color: var(--green);
        flex-shrink: 0;
    }
    .kpi .kpi-delta {
        margin-top: 6px;
        font-weight: 800;
        font-size: .7rem;
        color: var(--green);
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .kpi.kpi-blue .kpi-icon { background: rgba(59,130,246,.12); color: var(--blue); }
    .kpi.kpi-blue .kpi-delta { color: var(--blue); }
    .kpi.kpi-orange .kpi-icon { background: rgba(245,158,11,.14); color: var(--orange); }
    .kpi.kpi-orange .kpi-delta { color: var(--orange); }
    .kpi.kpi-red .kpi-icon { background: rgba(239,68,68,.14); color: var(--red); }
    .kpi.kpi-red .kpi-delta { color: var(--red); }
    .kpi.kpi-purple .kpi-icon { background: rgba(139,92,246,.14); color: var(--purple); }
    .kpi.kpi-purple .kpi-delta { color: var(--purple); }

    .dot {
        height: 8px;
        width: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .widget-title { font-weight: 900; margin: 0; font-size: .85rem; }
    .widget-sub { color: var(--muted); font-weight: 700; font-size: .72rem; }
    .progress-thin { height: 6px; border-radius: 999px; background: rgba(15,23,42,.06); overflow: hidden; }

    .mini-item {
        border-bottom: 1px dashed rgba(2,6,23,.06);
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    .mini-item:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }

    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 991.98px) {
        .sidebar-desktop { display: none !important; }
        main { margin-left: 0 !important; padding: 16px !important; }
        .top-bar-mobile { display: flex; }
    }
    </style>
</head>
<body>

    <div class="top-bar-mobile align-items-center justify-content-between w-100 d-lg-none shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-logo text-white bg-success bg-opacity-25">S</div>
            <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
        </div>
        <button class="btn btn-success btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            <i class="bi bi-list fs-5"></i>
        </button>
    </div>

    <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" style="background: var(--sidebar); width: 270px;">
        <div class="offcanvas-header border-bottom border-secondary border-opacity-20">
            <div class="d-flex align-items-center gap-2">
                <div class="brand-logo">S</div>
                <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body sidebar-nav p-3 d-flex flex-column justify-content-between" style="height: calc(100vh - 70px); overflow-y: auto;">
            <div>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item"><a href="Dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Tableau de bord</a></li>
                </ul>

                <div class="sidebar-title">Gestion de la plateforme</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item"><a href="utilisateurs.php" class="nav-link"><i class="bi bi-people"></i> Utilisateurs</a></li>
                    <li class="nav-item"><a href="municipalites.php" class="nav-link"><i class="bi bi-building"></i> Municipalités</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Catégories</a></li>
                    <li class="nav-item"><a href="zones.php" class="nav-link"><i class="bi bi-geo-alt"></i> Zones</a></li>
                    <li class="nav-item"><a href="equipes.php" class="nav-link"><i class="bi bi-shield"></i> Équipes</a></li>
                    <li class="nav-item"><a href="parametres.php" class="nav-link"><i class="bi bi-gear"></i> Paramètres</a></li>
                </ul>

                <div class="sidebar-title">Opérations</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone"></i> Signalements</a></li>
                    <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools"></i> Interventions</a></li>
                    <li class="nav-item"><a href="rapports.php" class="nav-link"><i class="bi bi-file-earmark-bar-graph"></i> Rapports</a></li>
                </ul>

                <div class="sidebar-title">Système</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                </ul>
            </div>

            <div class="pt-2 border-top border-secondary border-opacity-40 mt-4">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo htmlspecialchars($admin_avatar); ?>" class="rounded-circle border border-white border-opacity-20" width="35" height="35" style="object-fit: cover; background-color: rgba(255,255,255,0.1);" alt="Avatar">
                    <div>
                        <span class="text-white fw-bold d-block" style="font-size: 10px;"><?= htmlspecialchars($admin_name) ?></span>
                        <small class="text-muted" style="font-size: 9px;">Super Administrateur</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex w-100">

        <aside class="sidebar-desktop sidebar-nav d-none d-lg-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-4 px-2">
                    <div class="brand-logo">S</div>
                    <div>
                        <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
                        <span class="text-muted" style="font-size: 8px; display:block;">Pour des villes plus propres</span>
                    </div>
                </div>

                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a href="Dashboard.php" class="nav-link active">
                            <i class="bi bi-grid-1x2-fill"></i> Tableau de bord
                        </a>
                    </li>
                </ul>

                <div class="sidebar-title">Gestion de la plateforme</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item"><a href="utilisateurs.php" class="nav-link"><i class="bi bi-people"></i> Utilisateurs</a></li>
                    <li class="nav-item"><a href="municipalites.php" class="nav-link"><i class="bi bi-building"></i> Municipalités</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Catégories</a></li>
                    <li class="nav-item"><a href="zones.php" class="nav-link"><i class="bi bi-geo-alt"></i> Zones</a></li>
                    <li class="nav-item"><a href="equipes.php" class="nav-link"><i class="bi bi-shield"></i> Équipes</a></li>
                    <li class="nav-item"><a href="parametres.php" class="nav-link"><i class="bi bi-gear"></i> Paramètres généraux</a></li>
                </ul>

                <div class="sidebar-title">Opérations</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone"></i> Signalements</a></li>
                    <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools"></i> Interventions</a></li>
                    <li class="nav-item"><a href="equipes_terrain.php" class="nav-link"><i class="bi bi-person-badge"></i> Équipes terrain</a></li>
                    <li class="nav-item"><a href="rapports.php" class="nav-link"><i class="bi bi-file-earmark-bar-graph"></i> Rapports & Analyses</a></li>
                    <li class="nav-item"><a href="calendrier.php" class="nav-link"><i class="bi bi-calendar3"></i> Calendrier</a></li>
                </ul>

                <div class="sidebar-title">Système</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                </ul>
            </div>

            <div class="mt-4">
                <div class="help-box mb-3 text-white text-center">
                    <p class="m-0 fw-bold" style="font-size: 10px;"><i class="bi bi-headset"></i> Besoin d'aide ?</p>
                    <span class="text-muted d-block" style="font-size: 9px;">Consultez la documentation</span>
                </div>

                <div class="d-flex align-items-center gap-2 pt-2 border-top border-secondary">
                    <div style="width: 35px; height: 35px; flex-shrink: 0;">
                        <img src="<?php echo htmlspecialchars($admin_avatar); ?>" class="rounded-circle border border-white border-opacity-20" width="35" height="35" style="object-fit: cover; display: block; background-color: rgba(255,255,255,0.1);" alt="Avatar">
                    </div>
                    <div>
                        <span class="text-white fw-bold d-block" style="font-size: 10px;"><?= htmlspecialchars($admin_name) ?></span>
                        <small class="text-muted" style="font-size: 9px;">Super Administrateur</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="p-3 p-md-4">
            <header class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h4 class="fw-bold m-0">Dashboard Administrateur</h4>
                    <small class="text-muted">Vue d'ensemble de la plateforme SENEGALSET</small>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 w-100 w-md-auto">
                    <div class="input-group input-group-sm bg-white rounded shadow-sm flex-grow-1 flex-md-grow-0" style="min-width: 260px; max-width: 380px;">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 bg-transparent" placeholder="Rechercher...">
                    </div>

                    <div class="d-flex align-items-center gap-3 ms-auto ms-md-0">
                        <a href="signalements.php" class="position-relative text-decoration-none">
                            <i class="bi bi-bell text-muted fs-5"></i>
                            <?php if ($total_notifications > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 7px;">
                                    <?= $total_notifications ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div style="width: 35px; height: 35px; flex-shrink: 0;">
                            <img src="<?php echo htmlspecialchars($admin_avatar); ?>" class="rounded-circle border" width="35" height="35" style="object-fit: cover; display: block; background-color: rgba(0,0,0,0.05);" alt="Avatar">
                        </div>
                    </div>
                </div>
            </header>

            <div class="d-flex justify-content-end mb-3">
                <div class="bg-white px-3 py-1 rounded shadow-sm border border-light text-muted" style="font-size: .78rem; font-weight: 800;">
                    <i class="bi bi-calendar3 me-2"></i> Juin 2026
                </div>
            </div>

            <div class="row g-2 g-md-3 mb-4">
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card-soft kpi">
                        <div class="kpi-left">
                            <small class="d-block text-truncate">Signalements</small>
                            <h5><?= $total_reports ?></h5>
                            <span class="kpi-delta">↗ +18%</span>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-megaphone"></i></div>
                    </div>
                </div>

                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card-soft kpi kpi-orange">
                        <div class="kpi-left">
                            <small class="d-block text-truncate">En attente</small>
                            <h5><?= $attente_reports ?></h5>
                            <span class="kpi-delta">↘ -8%</span>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
                    </div>
                </div>

                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card-soft kpi kpi-blue">
                        <div class="kpi-left">
                            <small class="d-block text-truncate">En cours</small>
                            <h5><?= $encours_reports ?></h5>
                            <span class="kpi-delta">↗ +12%</span>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-arrow-repeat"></i></div>
                    </div>
                </div>

                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card-soft kpi">
                        <div class="kpi-left">
                            <small class="d-block text-truncate">Résolus</small>
                            <h5><?= $resolu_reports ?></h5>
                            <span class="kpi-delta">↗ +22%</span>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-check2-circle"></i></div>
                    </div>
                </div>

                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card-soft kpi kpi-red">
                        <div class="kpi-left">
                            <small class="d-block text-truncate">Rejetés</small>
                            <h5><?= $rejete_reports ?></h5>
                            <span class="kpi-delta">↘ -5%</span>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-x-circle"></i></div>
                    </div>
                </div>

                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card-soft kpi kpi-purple">
                        <div class="kpi-left">
                            <small class="d-block text-truncate">Utilisateurs</small>
                            <h5><?= $total_users ?></h5>
                            <span class="kpi-delta">↗ +15%</span>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-people"></i></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card-soft p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="widget-title">Signalements par statut</h6>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 110px; height: 110px; flex-shrink:0;">
                                <canvas id="statusDonutChart"></canvas>
                            </div>
                            <ul class="mini-list flex-grow-1 m-0 p-0" style="list-style:none; font-size: 11px;">
                                <li class="mb-1"><span class="dot" style="background:#00a651;"></span> Résolus: <strong><?= $resolu_reports ?></strong></li>
                                <li class="mb-1"><span class="dot" style="background:#3b82f6;"></span> En cours: <strong><?= $encours_reports ?></strong></li>
                                <li class="mb-1"><span class="dot" style="background:#f59e0b;"></span> Attente: <strong><?= $attente_reports ?></strong></li>
                                <li><span class="dot" style="background:#ef4444;"></span> Rejetés: <strong><?= $rejete_reports ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card-soft p-3 h-100">
                        <h6 class="widget-title mb-3">Signalements par catégorie</h6>
                        <?php foreach ($categories_dist as $cat):
                            $count = (int)($cat['count_cat'] ?? 0);
                            $pct = $total_reports > 0 ? round(($count / $total_reports) * 100) : 0;
                        ?>
                        <div class="mini-item">
                            <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                                <span style="font-weight:700; color:#475569;"><?= htmlspecialchars($cat['name'] ?? '') ?></span>
                                <strong><?= $count ?> (<?= $pct ?>%)</strong>
                            </div>
                            <div class="progress-thin mt-1"><div class="bg-success h-100" style="width: <?= $pct ?>%"></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 col-md-12 col-lg-4">
                    <div class="card-soft p-3 h-100">
                        <h6 class="widget-title mb-2">Signalements par municipalité</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <?php foreach ($zones_dist as $z): ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1" style="font-size:11px;">
                                        <span>📍 <?= htmlspecialchars($z['name'] ?? '') ?></span>
                                        <strong><?= (int)($z['count_zone'] ?? 0) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card-soft p-3 h-100">
                        <h6 class="widget-title mb-2">Évolution des signalements</h6>
                        <div style="height: 160px;"><canvas id="lineChartEvolution"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card-soft p-3 h-100 text-center">
                        <h6 class="widget-title mb-2">Interventions ce mois</h6>
                        <div class="d-flex justify-content-center align-items-center" style="height: 160px;">
                            <div style="width: 140px; height: 140px;"><canvas id="intervDonutChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card-soft p-3 h-100">
                        <h6 class="widget-title mb-2">Activités récentes</h6>
                        <div style="max-height: 170px; overflow-y:auto;">
                            <?php foreach ($notifications as $n): ?>
                                <div class="d-flex gap-2 align-items-start p-2 rounded border mb-2" style="font-size:11px;">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    <div><strong><?= htmlspecialchars($n['title'] ?? '') ?></strong><div class="text-muted"><?= htmlspecialchars($n['description'] ?? '') ?></div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    new Chart(document.getElementById('statusDonutChart').getContext('2d'), {
        type: 'doughnut',
        data: { datasets: [{ data: [<?= (int)$resolu_reports ?>, <?= (int)$encours_reports ?>, <?= (int)$attente_reports ?>, <?= (int)$rejete_reports ?>], backgroundColor: ['#00a651', '#3b82f6', '#f59e0b', '#ef4444'], borderWidth: 0 }] },
        options: { cutout: '72%', plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('lineChartEvolution').getContext('2d'), {
        type: 'line',
        data: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], datasets: [{ data: [12, 19, 32, 45, 62, <?= (int)$resolu_reports ?>], borderColor: '#00a651', tension: 0.25, fill: true }, { data: [5, 12, 15, 22, 28, <?= (int)$encours_reports ?>], borderColor: '#3b82f6', tension: 0.25, fill: true }] },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('intervDonutChart').getContext('2d'), {
        type: 'doughnut',
        data: { datasets: [{ data: [75, 20, 5], backgroundColor: ['#00a651', '#3b82f6', '#ef4444'], borderWidth: 0 }] },
        options: { cutout: '72%', plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });
});
</script>
</body>
</html> 