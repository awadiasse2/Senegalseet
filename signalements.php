<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
require_once '../config/config.php';

$user_id = $_SESSION['user_id'];

// RÉCUPÉRATION DU PROFIL EN TEMPS RÉEL
try {
    $stmt_user = $pdo->prepare("SELECT name, role, avatar FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_user = null;
}

$user_name = !empty($current_user['name']) ? $current_user['name'] : 'Citoyen';
$user_role = !empty($current_user['role']) ? $current_user['role'] : 'Client';

if (!empty($current_user['avatar'])) {
    if (strpos($current_user['avatar'], 'http') === 0) {
        $user_avatar = $current_user['avatar'];
    } else {
        $user_avatar = "../uploads/avatars/" . $current_user['avatar'];
    }
} else {
    $user_avatar = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";
}

$filter = $_GET['status'] ?? 'Tous';
$search = $_GET['search'] ?? '';

$query = "SELECT r.*, z.name as zone_name, c.name as cat_name 
          FROM reports r 
          LEFT JOIN zones z ON r.zone_id = z.id
          LEFT JOIN categories c ON r.category_id = c.id WHERE r.user_id = ?";
$params = [$user_id];

if ($filter !== 'Tous') {
    $query .= " AND r.status = ?";
    $params[] = $filter;
}
if (!empty($search)) {
    $query .= " AND (z.name LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();
$total_reports_count = count($reports);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Signalements — SenegalSet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        }
        
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

        /* Top Bar Mobile */
        .top-bar-mobile {
            display: none;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 20px;
        }

        .header-avatar { object-fit: cover; border: 1px solid #e2e8f0; }
        
        /* Style des images responsive */
        .report-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }

        @media (max-width: 575.98px) {
            .report-img {
                width: 100%;
                height: 160px;
            }
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 991.98px) {
            .sidebar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 20px !important; }
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
                        <li class="nav-item"><a href="espace_client.php" class="nav-link rounded p-2.5"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
                        <li class="nav-item"><a href="signalements.php" class="nav-link active rounded p-2.5"><i class="bi bi-megaphone me-2"></i> Mes signalements</a></li>
                        <li class="nav-item"><a href="interventions.php" class="nav-link rounded p-2.5"><i class="bi bi-tools me-2"></i> Interventions</a></li>
                        <li class="nav-item"><a href="calendrier.php" class="nav-link rounded p-2.5"><i class="bi bi-calendar3 me-2"></i> Calendrier</a></li>
                        <li class="nav-item"><a href="rapports.php" class="nav-link rounded p-2.5"><i class="bi bi-file-earmark-bar-graph me-2"></i> Rapports</a></li>
                        <li class="nav-item"><a href="carte.php" class="nav-link rounded p-2.5"><i class="bi bi-map me-2"></i> Carte</a></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="border-top border-secondary border-opacity-30 pt-3">
                    <a href="../auth/logout.php" class="text-danger text-decoration-none text-sm fw-medium d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-left"></i> Déconnexion
                    </a>
                </div>
            </div>
        </aside>

        <!-- MENU MOBILE TIROIR (Offcanvas) -->
        <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileSidebar" style="width: var(--sidebar-width);">
            <div class="offcanvas-header border-bottom border-secondary border-opacity-20">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success p-2 text-white fw-bold d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">SS</div>
                    <h6 class="m-0 fw-bold text-white">SenegalSet</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column justify-content-between p-4">
                <ul class="nav flex-column gap-1">
                    <li class="nav-item"><a href="espace_client.php" class="nav-link rounded p-2.5"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
                    <li class="nav-item"><a href="signalements.php" class="nav-link active rounded p-2.5"><i class="bi bi-megaphone me-2"></i> Mes signalements</a></li>
                    <li class="nav-item"><a href="interventions.php" class="nav-link rounded p-2.5"><i class="bi bi-tools me-2"></i> Interventions</a></li>
                    <li class="nav-item"><a href="carte.php" class="nav-link rounded p-2.5"><i class="bi bi-map me-2"></i> Carte</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link rounded p-2.5 text-danger"><i class="bi bi-box-arrow-left me-2"></i> Déconnexion</a></li>
                </ul>
            </div>
        </div>

        <!-- HEADER MOBILE -->
        <div class="top-bar-mobile justify-content-between align-items-center w-100">
            <button class="btn btn-light border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <i class="bi bi-list fs-4"></i>
            </button>
            <span class="fw-bold">Mes Signalements (<?= $total_reports_count ?>)</span>
            <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle object-fit-cover" width="32" height="32">
        </div>

        <!-- CONTENU CENTRAL -->
        <main class="flex-grow-1 p-4 main-content">
            
            <!-- HEADER DE BUREAU -->
            <header class="d-none d-lg-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="m-0 fw-bold text-dark">Mes Signalements (<?= $total_reports_count ?>)</h4>
                </div>
                
                <div class="d-flex align-items-center gap-2 bg-white p-1 pe-3 border rounded-4 shadow-sm">
                    <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle header-avatar" width="40" height="40" alt="Avatar">
                    <div>
                        <p class="m-0 fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($user_name) ?></p>
                        <small class="text-muted d-block" style="font-size: 10px; text-transform: capitalize;">Espace <?= htmlspecialchars($user_role) ?></small>
                    </div>
                </div>
            </header>

            <!-- CONTENEUR DES CARTES DE SIGNALEMENT -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($reports)): ?>
                        <div class="alert alert-info text-center rounded-4 border-0 shadow-sm p-4">
                            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                            Aucun signalement ne correspond à vos critères.
                        </div>
                    <?php else: ?>
                        <?php foreach ($reports as $report): 
                            $slug_status = strtolower(str_replace(' ', '-', $report['status']));
                            
                            // GESTION DU CHEMIN DE L'IMAGE
                            if (!empty($report['image'])) {
                                $report_img_path = "../uploads/reports/" . $report['image'];
                            } else {
                                $report_img_path = "https://images.unsplash.com/photo-1584467541268-b040f83be3fd?auto=format&fit=crop&w=150&q=80";
                            }

                            // Couleurs dynamiques selon le statut
                            $badge_color = 'bg-secondary';
                            if ($report['status'] === 'En attente') $badge_color = 'bg-warning text-dark';
                            elseif ($report['status'] === 'En cours') $badge_color = 'bg-primary';
                            elseif ($report['status'] === 'Résolu') $badge_color = 'bg-success';
                            elseif ($report['status'] === 'Rejeté') $badge_color = 'bg-danger';
                        ?>
                            <div class="report-card p-3 mb-3 bg-white border border-light-subtle rounded-4 shadow-sm">
                                <div class="d-flex flex-column flex-sm-row gap-3 align-items-start align-items-sm-center">
                                    
                                    <!-- Image adaptative -->
                                    <img src="<?= htmlspecialchars($report_img_path) ?>" class="report-img" alt="Illustration anomalie">

                                    <!-- Texte et informations -->
                                    <div class="flex-grow-1 w-100 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark">
                                                <?= htmlspecialchars($report['title'] ?? $report['cat_name'] ?? 'Anomalie sans titre') ?>
                                            </h6>
                                            <p class="text-muted text-sm mb-2" style="font-size: 13px; max-width: 600px;"><?= htmlspecialchars($report['description']) ?></p>
                                            
                                            <div class="d-flex gap-3 flex-wrap">
                                                <small class="text-xs text-muted" style="font-size: 11px;"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($report['zone_name'] ?? 'Zone inconnue') ?></small>
                                                <?php if(!empty($report['latitude']) && !empty($report['longitude'])): ?>
                                                    <small class="text-xs text-success" style="font-size: 11px;"><i class="bi bi-pin-map-fill me-1"></i>Coordonnées GPS</small>
                                                <?php endif; ?>
                                                <small class="text-xs text-muted" style="font-size: 11px;"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y à H:i', strtotime($report['created_at'])) ?></small>
                                            </div>
                                        </div>
                                        
                                        <!-- Badge de statut aligné à droite ou en bas selon taille écran -->
                                        <div class="text-md-end w-sm-100">
                                            <span class="badge <?= $badge_color ?> px-3 py-2 rounded-pill shadow-sm" style="font-size: 12px; font-weight: 600;">
                                                <?= htmlspecialchars($report['status']) ?>
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- Script Bootstrap pour l'ouverture du tiroir mobile -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>