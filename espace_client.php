<?php
// client/espace_client.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';

// ==========================================
// RÉCUPÉRATION DU PROFIL RÉEL
// ==========================================
$user_id = $_SESSION['user_id'];
try {
    $stmt_user = $pdo->prepare("SELECT name, role, avatar FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_user = null;
}

// Définition dynamique du nom et du rôle
$user_name = !empty($current_user['name']) ? $current_user['name'] : 'Utilisateur';
$user_role = !empty($current_user['role']) ? $current_user['role'] : 'client';

// Correction structurelle du chemin de l'avatar
if (!empty($current_user['avatar'])) {
    if (strpos($current_user['avatar'], 'http') === 0) {
        $user_avatar = $current_user['avatar'];
    } else {
        $user_avatar = "../uploads/avatars/" . $current_user['avatar'];
    }
} else {
    $user_avatar = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";
}
// ==========================================

// CORRECTION : Gestion de la recherche filtrée par utilisateur connecté
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = "";
$params_stats = [$user_id];

if (!empty($search_query)) {
    $search_sql = " AND (c.name LIKE ? OR z.name LIKE ? OR r.status LIKE ?)";
}

// CORRECTION : Statistiques restreintes strictement à l'utilisateur connecté
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN r.status = 'En attente' THEN 1 ELSE 0 END) as attente,
    SUM(CASE WHEN r.status = 'En cours' THEN 1 ELSE 0 END) as encours,
    SUM(CASE WHEN r.status = 'Résolu' THEN 1 ELSE 0 END) as resolu,
    SUM(CASE WHEN r.status = 'Rejeté' THEN 1 ELSE 0 END) as rejete
FROM reports r
LEFT JOIN categories c ON r.category_id = c.id
LEFT JOIN zones z ON r.zone_id = z.id
WHERE r.user_id = ?";

if (!empty($search_query)) {
    $stats_query .= $search_sql;
    $params_stats = [$user_id, "%$search_query%", "%$search_query%", "%$search_query%"];
}

$stmt = $pdo->prepare($stats_query);
$stmt->execute($params_stats);
$counts = $stmt->fetch();

$total_reports   = $counts['total'] ?? 0;
$attente_reports = $counts['attente'] ?? 0;
$encours_reports = $counts['encours'] ?? 0;
$resolu_reports  = $counts['resolu'] ?? 0;
$rejete_reports  = $counts['rejete'] ?? 0;

$p_attente = $total_reports > 0 ? round(($attente_reports / $total_reports) * 100) : 0;
$p_encours = $total_reports > 0 ? round(($encours_reports / $total_reports) * 100) : 0;
$p_resolu  = $total_reports > 0 ? round(($resolu_reports / $total_reports) * 100) : 0;
$p_rejete  = $total_reports > 0 ? round(($rejete_reports / $total_reports) * 100) : 0;

// CORRECTION : Interventions liées uniquement aux signalements de cet utilisateur
$stmt_interv = $pdo->prepare("SELECT i.*, c.name as report_title, z.name as zone_name 
    FROM interventions i 
    LEFT JOIN reports r ON i.report_id = r.id
    LEFT JOIN zones z ON r.zone_id = z.id
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE r.user_id = ? AND DATE(i.start_time) = CURDATE() 
    ORDER BY i.start_time ASC");
$stmt_interv->execute([$user_id]);
$interventions = $stmt_interv->fetchAll();
$total_interventions_du_jour = count($interventions);

// CORRECTION : Répartition par catégorie restreinte aux signalements de l'utilisateur
$stmt_cat = $pdo->prepare("SELECT c.name, COUNT(r.id) as count_cat 
    FROM categories c 
    LEFT JOIN reports r ON r.category_id = c.id AND r.user_id = ?
    GROUP BY c.id");
$stmt_cat->execute([$user_id]);
$categories_dist = $stmt_cat->fetchAll();

// CORRECTION : Activités récentes uniquement liées à l'utilisateur connecté
$recent_query = "SELECT r.*, z.name as zone_name, c.name as cat_name 
    FROM reports r 
    LEFT JOIN zones z ON r.zone_id = z.id
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE r.user_id = ?";

$params_recent = [$user_id];
if (!empty($search_query)) {
    $recent_query .= " AND (c.name LIKE ? OR z.name LIKE ? OR r.status LIKE ?)";
    $params_recent = [$user_id, "%$search_query%", "%$search_query%", "%$search_query%"];
}
$recent_query .= " ORDER BY r.created_at DESC LIMIT 3";

$stmt_recent = $pdo->prepare($recent_query);
$stmt_recent->execute($params_recent);
$recent_activities = $stmt_recent->fetchAll();

// Extraction des coordonnées
$map_markers = [];
foreach ($recent_activities as $act) {
    if (!empty($act['latitude']) && !empty($act['longitude'])) {
        $map_markers[] = [
            'lat' => (float)$act['latitude'],
            'lng' => (float)$act['longitude'],
            'title' => ($act['cat_name'] ?? 'Signalement') . " (" . ($act['zone_name'] ?? 'Zone non spécifiée') . ")"
        ];
    }
}

$stmt_alert_48h = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
try {
    $stmt_alert_48h->execute([$user_id]);
    $alert_48h_count = $stmt_alert_48h->fetchColumn();
} catch (Exception $e) {
    $alert_48h_count = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SenegalSet - Espace client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --emerald: #00a651;
            --bg-body: #f8fafc;
            --sidebar-width: 260px;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body);
            color: #1e293b;
            overflow-x: hidden;
            padding-bottom: 75px; /* Évite la superposition avec la barre mobile */
        }
        @media (min-width: 992px) {
            body { padding-bottom: 0; }
        }
        
        #map { width: 100%; height: 260px; border-radius: 12px; z-index: 1; }
        .stat-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
        
        /* Sidebar PC fixée */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #1e293b;
            z-index: 100;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .nav-link {
            color: #94a3b8;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff !important;
        }

        /* Top Bar Mobile standardisée */
        .top-bar-mobile {
            display: none;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
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

        /* RESPONSIVE DESIGN */
        @media (max-width: 991.98px) {
            .sidebar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 16px !important; }
            .top-bar-mobile { display: flex; }
        }
    </style>
</head>
<body>

    <div class="d-flex flex-column flex-lg-row">
        
        <!-- SIDEBAR DE BUREAU -->
        <aside class="sidebar p-4 d-none d-lg-flex flex-column justify-content-between">
            <div>
                <div class="brand mb-4 d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success p-2 text-white fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">SS</div>
                    <div>
                        <h6 class="m-0 fw-bold text-uppercase text-white">SenegalSet</h6>
                        <small class="text-muted text-xs">Mon espace client</small>
                    </div>
                </div>

                <div class="menu-section mb-4">
                    <small class="text-uppercase text-muted text-xs fw-bold d-block mb-3">Menu client</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item"><a href="espace_client.php" class="nav-link active rounded p-2.5"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
                        <li class="nav-item"><a href="signalements.php" class="nav-link rounded p-2.5"><i class="bi bi-megaphone me-2"></i> Mes signalements</a></li>
                        <li class="nav-item"><a href="interventions.php" class="nav-link rounded p-2.5"><i class="bi bi-tools me-2"></i> Interventions</a></li>
                        <li class="nav-item"><a href="carte.php" class="nav-link rounded p-2.5"><i class="bi bi-map me-2"></i> Carte</a></li>
                        <li class="nav-item"><a href="signaler.php" class="nav-link rounded p-2.5"><i class="bi bi-plus-circle me-2"></i> Créer un signalement</a></li>
                        <li class="nav-item"><a href="notifications.php" class="nav-link rounded p-2.5"><i class="bi bi-bell me-2"></i> Notifications <?php if ($alert_48h_count > 0): ?><span class="badge bg-danger rounded-pill ms-1"><?= $alert_48h_count ?></span><?php endif; ?></a></li>
                        <li class="nav-item"><a href="profil.php" class="nav-link rounded p-2.5"><i class="bi bi-person-circle me-2"></i> Mon profil</a></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="bg-dark bg-opacity-20 p-3 rounded-4 mb-3 text-white border border-secondary border-opacity-20">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calendar-check fs-4 text-success"></i>
                        <p class="m-0 text-xs fw-light">Suivi : <br><strong class="fs-6 fw-bold text-white"><?= $total_interventions_du_jour ?> intervention(s)</strong></p>
                    </div>
                </div>
                <div class="border-top border-secondary border-opacity-30 pt-3">
                    <a href="../auth/logout.php" class="text-danger text-decoration-none text-sm fw-medium d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-left"></i> Déconnexion
                    </a>
                </div>
            </div>
        </aside>

        <!-- HEADER MOBILE -->
        <div class="top-bar-mobile justify-content-between align-items-center w-100 shadow-sm">
            <span class="fw-bold text-dark"><i class="bi bi-grid-1x2-fill text-success me-2"></i>Mon Espace</span>
            <div class="d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle object-fit-cover border" width="35" height="35">
            </div>
        </div>

        <!-- CONTENU CENTRAL -->
        <main class="flex-grow-1 p-4 main-content">
            
            <!-- BARRE SUPÉRIEURE DE BUREAU -->
            <header class="d-none d-lg-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <form method="GET" action="espace_client.php" class="position-relative" style="width: 300px;">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" class="form-control ps-5 border-0 bg-white shadow-sm rounded-3" 
                           placeholder="Rechercher..." value="<?= htmlspecialchars($search_query) ?>">
                </form>

                <div class="d-flex align-items-center gap-3">
                    <a href="notifications.php" class="text-dark position-relative me-2">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if ($alert_48h_count > 0): ?> 
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $alert_48h_count ?></span>
                        <?php endif; ?> 
                    </a>
                    <div class="d-flex align-items-center gap-2 border-start ps-3">
                        <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle object-fit-cover" width="35" height="35">
                        <div class="text-start">
                            <p class="m-0 fw-semibold text-xs text-dark" style="font-size: 12px;"><?= htmlspecialchars($user_name) ?></p>
                            <small class="text-muted text-capitalize" style="font-size: 10px;"><?= htmlspecialchars($user_role) ?></small>
                        </div>
                    </div>
                </div>
            </header>

            <!-- TITRE BIENVENUE -->
            <div class="mb-4 text-center text-md-start">
                <h4 class="fw-bold m-0">Bonjour <?= htmlspecialchars($user_name) ?> 👋</h4>
                <p class="text-muted m-0 text-sm">Suivez l'état d'avancement de vos signalements citoyens en temps réel.</p>
            </div>

            <!-- FILTRE MOBILE -->
            <div class="d-block d-lg-none mb-4">
                <form method="GET" action="espace_client.php" class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" class="form-control ps-5 border-0 bg-white shadow-sm rounded-3" 
                           placeholder="Rechercher parmi vos dépôts..." value="<?= htmlspecialchars($search_query) ?>">
                </form>
            </div>

            <!-- CARTES STATISTIQUES RE-ORDONNÉES EN ROW FLUIDE -->
            <div class="row g-2 g-md-3 mb-4">
                <div class="col-6 col-sm-4 col-xl">
                    <div class="card shadow-sm p-3 h-100 border-0 rounded-4 bg-white">
                        <span class="stat-icon bg-light text-success mb-2"><i class="bi bi-megaphone-fill"></i></span>
                        <small class="text-muted text-xs text-uppercase fw-semibold" style="font-size:10px;">Vos dépôts</small>
                        <h4 class="fw-bold m-0 mt-1"><?= $total_reports ?></h4>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl">
                    <div class="card shadow-sm p-3 h-100 border-0 rounded-4 bg-white">
                        <span class="stat-icon bg-warning-subtle text-warning mb-2"><i class="bi bi-clock-history"></i></span>
                        <small class="text-muted text-xs text-uppercase fw-semibold" style="font-size:10px;">En attente</small>
                        <h4 class="fw-bold m-0 mt-1 text-warning"><?= $attente_reports ?></h4>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl">
                    <div class="card shadow-sm p-3 h-100 border-0 rounded-4 bg-white">
                        <span class="stat-icon bg-primary-subtle text-primary mb-2"><i class="bi bi-play-circle"></i></span>
                        <small class="text-muted text-xs text-uppercase fw-semibold" style="font-size:10px;">En cours</small>
                        <h4 class="fw-bold m-0 mt-1 text-primary"><?= $encours_reports ?></h4>
                    </div>
                </div>
                <div class="col-6 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100 border-0 rounded-4 bg-white">
                        <span class="stat-icon bg-success-subtle text-success mb-2"><i class="bi bi-check-circle-fill"></i></span>
                        <small class="text-muted text-xs text-uppercase fw-semibold" style="font-size:10px;">Résolus</small>
                        <h4 class="fw-bold m-0 mt-1 text-success"><?= $resolu_reports ?></h4>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100 border-0 rounded-4 bg-white">
                        <span class="stat-icon bg-danger-subtle text-danger mb-2"><i class="bi bi-x-circle-fill"></i></span>
                        <small class="text-muted text-xs text-uppercase fw-semibold" style="font-size:10px;">Rejetés</small>
                        <h4 class="fw-bold m-0 mt-1 text-danger"><?= $rejete_reports ?></h4>
                    </div>
                </div>
            </div>

            <!-- CONTENU DE GRILLE (1 colonne sur mobile, 2 sur PC) -->
            <div class="row g-3 g-md-4">
                <div class="col-12 col-lg-7">
                    <!-- Graphique & Proportions -->
                    <div class="card shadow-sm p-3 mb-4 border-0 rounded-4 bg-white">
                        <h6 class="fw-bold mb-3">Proportion de vos états</h6>
                        <div class="row align-items-center g-2">
                            <div class="col-6 d-flex justify-content-center">
                                <canvas id="statusChart" style="max-height: 120px; max-width: 120px;"></canvas>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled m-0 text-xs d-flex flex-column gap-2" style="font-size:11px;">
                                    <li><i class="bi bi-circle-fill text-warning me-1"></i> Attente: <strong><?= $attente_reports ?></strong></li>
                                    <li><i class="bi bi-circle-fill text-primary me-1"></i> En cours: <strong><?= $encours_reports ?></strong></li>
                                    <li><i class="bi bi-circle-fill text-success me-1"></i> Résolus: <strong><?= $resolu_reports ?></strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Interventions -->
                    <div class="card shadow-sm p-3 mb-4 border-0 rounded-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Interventions planifiées</h6>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <?php if (empty($interventions)): ?>
                                <p class="text-muted text-xs m-0 py-2"><i class="bi bi-info-circle me-1"></i> Aucune équipe prévue aujourd'hui.</p>
                            <?php else: ?>
                                <?php foreach ($interventions as $interv): 
                                    $badge_class = ($interv['status'] == 'En cours') ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning';
                                ?>
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
                                    <div>
                                        <p class="m-0 fw-semibold text-sm text-dark"><?= htmlspecialchars($interv['report_title'] ?? 'Intervention') ?></p>
                                        <small class="text-muted style-xs" style="font-size:11px;"><i class="bi bi-clock"></i> <?= date('H:i', strtotime($interv['start_time'])) ?></small>
                                    </div>
                                    <span class="badge <?= $badge_class ?> rounded-pill px-2 py-1 text-xs"><?= htmlspecialchars($interv['status']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <!-- Carte Leaflet -->
                    <div class="card shadow-sm p-3 mb-4 border-0 rounded-4 bg-white">
                        <h6 class="fw-bold mb-3 text-dark">Emplacements sur la carte</h6>
                        <div id="map"></div>
                    </div>

                    <!-- Alertes -->
                    <div class="card shadow-sm p-3 border-0 rounded-4 bg-white">
                        <h6 class="fw-bold mb-3 text-dark">Alertes sur vos dépôts</h6>
                        <?php if ($alert_48h_count > 0): ?>
                        <div class="p-3 alert alert-danger border-0 rounded-3 text-xs mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                            <strong><?= intval($alert_48h_count) ?> notification(s) non lue(s)</strong>
                        </div>
                        <?php else: ?>
                        <div class="p-2 alert alert-success border-0 rounded-3 text-xs mb-0 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span style="font-size:11px;">Aucune anomalie majeure sur vos dossiers.</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- BARRE DE NAVIGATION FIXE MOBILE (< 992px) -->
    <nav class="navbar bottom-nav fixed-bottom py-2 d-lg-none">
        <div class="container-fluid d-flex justify-content-around">
            <a href="espace_client.php" class="nav-link-custom active"><i class="bi bi-house-fill"></i>Accueil</a>
            <a href="signaler.php" class="nav-link-custom"><i class="bi bi-megaphone"></i>Signaler</a>
            <a href="carte.php" class="nav-link-custom"><i class="bi bi-map"></i>Carte</a>
            <a href="notifications.php" class="nav-link-custom position-relative">
                <i class="bi bi-bell"></i>
                <?php if ($alert_48h_count > 0): ?>
                    <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger" style="font-size:8px; margin-left: 8px;">
                        <?= $alert_48h_count ?>
                    </span>
                <?php endif; ?>
                Notifs
            </a>
            <a href="profil.php" class="nav-link-custom"><i class="bi bi-person"></i>Profil</a>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Centrage par défaut (Sénégal / Kaolack)
            const map = L.map('map').setView([14.1438, -16.0712], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            const phpMarkers = <?= json_encode($map_markers); ?>;
            if (phpMarkers.length > 0) {
                const points = [];
                phpMarkers.forEach(m => {
                    L.marker([m.lat, m.lng]).addTo(map).bindPopup(`<b class="text-xs">${m.title}</b>`);
                    points.push([m.lat, m.lng]);
                });
                map.fitBounds(points, {padding: [30, 30]});
            }
        });

        // Graphique circulaire
        const ctx = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Attente', 'En cours', 'Résolus', 'Rejetés'],
                datasets: [{ 
                    data: [<?= (int)$attente_reports ?>, <?= (int)$encours_reports ?>, <?= (int)$resolu_reports ?>, <?= (int)$rejete_reports ?>], 
                    backgroundColor: ['#ffb703', '#0d6efd', '#198754', '#dc3545'],
                    borderWidth: 0 
                }]
            },
            options: { plugins: { legend: { display: false } }, cutout: '75%' }
        });
    </script>
</body>
</html>