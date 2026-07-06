<?php
// index.php - Redirect to Dashboard
session_start();

// 1. SÉCURITÉ : Bloquer l'accès si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// 2. SÉCURITÉ : Bloquer l'accès si l'utilisateur n'est pas admin
$user_role = trim(strtolower($_SESSION['user_role'] ?? ''));
if ($user_role !== 'admin') {
    header('Location: ../Client/index.php');
    exit();
}

require_once '../config/config.php';

// Récupération dynamique des données de l'utilisateur connecté via la session
$user_name   = $_SESSION['user_name'] ?? 'Utilisateur';
$user_role   = $_SESSION['user_role'] ?? 'Agent';
$user_avatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'https://via.placeholder.com/35';

// 2. Récupération des compteurs globaux (Top Cards)
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'En attente' THEN 1 ELSE 0 END) as attente,
    SUM(CASE WHEN status = 'En cours' THEN 1 ELSE 0 END) as encours,
    SUM(CASE WHEN status = 'Résolu' THEN 1 ELSE 0 END) as resolu,
    SUM(CASE WHEN status = 'Rejeté' THEN 1 ELSE 0 END) as rejete
FROM reports");
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

$total_reports   = $counts['total'] ?? 0;
$attente_reports = $counts['attente'] ?? 0;
$encours_reports = $counts['encours'] ?? 0;
$resolu_reports  = $counts['resolu'] ?? 0;
$rejete_reports  = $counts['rejete'] ?? 0;

// Calcul des pourcentages pour le graphique et les barres
$p_attente = $total_reports > 0 ? round(($attente_reports / $total_reports) * 100) : 0;
$p_encours = $total_reports > 0 ? round(($encours_reports / $total_reports) * 100) : 0;
$p_resolu  = $total_reports > 0 ? round(($resolu_reports / $total_reports) * 100) : 0;
$p_rejete  = $total_reports > 0 ? round(($rejete_reports / $total_reports) * 100) : 0;

// 3. Récupération des interventions programmées du jour (Date courante)
$stmt_interv = $pdo->query("SELECT i.*, z.name as zone_name 
    FROM interventions i 
    LEFT JOIN reports r ON i.report_id = r.id
    LEFT JOIN zones z ON r.zone_id = z.id
    WHERE DATE(i.start_time) = CURDATE() 
    ORDER BY i.start_time ASC");
$interventions = $stmt_interv->fetchAll(PDO::FETCH_ASSOC);
$total_interventions_du_jour = count($interventions);

// 4. Récupération de la répartition par catégorie
$stmt_cat = $pdo->query("SELECT c.name, COUNT(r.id) as count_cat 
    FROM categories c 
    LEFT JOIN reports r ON r.category_id = c.id 
    GROUP BY c.id");
$categories_dist = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 5. Activité récente (3 derniers signalements créés)
$stmt_recent = $pdo->query("SELECT r.*, z.name as zone_name, c.name as cat_name 
    FROM reports r 
    LEFT JOIN zones z ON r.zone_id = z.id
    LEFT JOIN categories c ON r.category_id = c.id
    ORDER BY r.created_at DESC LIMIT 3");
$recent_activities = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

// 6. Alertes : Calcul des signalements en attente depuis + de 48 heures
$stmt_alert_48h = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'En attente' AND created_at <= NOW() - INTERVAL 2 DAY");
$alert_48h_count = $stmt_alert_48h->fetchColumn();

// Traduction locale des jours en français au cas où l'extension Intl n'est pas installée
$jours_fr = ['Sunday' => 'dimanche', 'Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi', 'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi'];
$jour_semaine = $jours_fr[date('l')] ?? date('l');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        #map {
            width: 100%;
            height: 260px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="d-flex">
        <aside class="sidebar p-3 d-flex flex-column justify-content-between">
            <div>
                <div class="brand mb-4 d-flex align-items-center gap-2">
                    <div class="logo-placeholder rounded-circle p-2 text-white fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #198754;">SS</div>
                    <div>
                        <h6 class="m-0 fw-bold tracking-wide text-uppercase text-white">SenegalSet</h6>
                        <small class="text-sidebar-muted text-xs">Pour des villes plus propres et connectées</small>
                    </div>
                </div>

                <div class="menu-section mb-4">
                    <small class="text-uppercase text-sidebar-muted text-xs fw-bold tracking-wider d-block mb-2">Menu Principal</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item"><a href="index.php" class="nav-link active rounded"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
                        <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone me-2"></i> Signalements</a></li>
                        <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools me-2"></i> Interventions</a></li>
                        <li class="nav-item"><a href="equipes.php" class="nav-link"><i class="bi bi-people me-2"></i> Équipes</a></li>
                        <li class="nav-item"><a href="calendrier.php" class="nav-link"><i class="bi bi-calendar3 me-2"></i> Calendrier</a></li>
                        <li class="nav-item"><a href="rapports.php" class="nav-link"><i class="bi bi-file-earmark-bar-graph me-2"></i> Rapports</a></li>
                        <li class="nav-item"><a href="carte.php" class="nav-link"><i class="bi bi-map me-2"></i> Carte</a></li>
                    </ul>
                </div>

                <div class="menu-section mb-4">
                    <small class="text-uppercase text-sidebar-muted text-xs fw-bold tracking-wider d-block mb-2">Gestion</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item"><a href="categories.php" class="nav-link"><i class="bi bi-tags me-2"></i> Catégories</a></li>
                        <li class="nav-item"><a href="zones.php" class="nav-link"><i class="bi bi-geo-alt me-2"></i> Zones</a></li>
                        <li class="nav-item"><a href="utilisateurs.php" class="nav-link"><i class="bi bi-person me-2"></i> Utilisateurs</a></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="sidebar-alert-box p-3 rounded mb-3 text-white bg-success bg-opacity-75">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calendar-check fs-4"></i>
                        <p class="m-0 text-xs fw-light">Vous avez <br><strong class="fs-6 fw-bold"><?= intval($total_interventions_du_jour) ?> intervention(s)</strong> aujourd'hui</p>
                    </div>
                    <a href="calendrier.php" class="btn btn-light btn-sm w-100 fw-semibold text-xs py-2 rounded-3 text-dark text-decoration-none text-center d-block">Voir le planning</a>
                </div>
                <div class="sidebar-footer border-top border-secondary pt-3">
                    <a href="../auth/logout.php" class="text-danger text-decoration-none text-sm fw-medium d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-left"></i> Déconnexion
                    </a>
                </div>
            </div>
        </aside>

        <main class="flex-grow-1 p-4 main-content">
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn p-0 border-0 fs-4 text-dark"><i class="bi bi-list"></i></button>
                    <div class="search-bar position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" class="form-control ps-5 border-0 shadow-sm" placeholder="Rechercher un signalement, une équipe...">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-white position-relative shadow-sm bg-white border-0 p-2 rounded-circle"><i class="bi bi-bell"></i><span class="badge-dot-red"></span></button>
                    <div class="d-flex align-items-center gap-2 px-2">
                        <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle border" width="35" height="35" alt="Avatar">
                        <div class="text-start">
                            <p class="m-0 fw-semibold text-xs text-dark"><?= htmlspecialchars($user_name) ?></p>
                            <small class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($user_role) ?></small>
                        </div>
                    </div>
                    
                    <div class="bg-white px-3 py-2 rounded shadow-sm text-sm fw-medium text-muted text-center">
                        <i class="bi bi-calendar-event me-2 text-success"></i> 
                        <?php
                        if (class_exists('IntlDateFormatter')) {
                            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
                            echo htmlspecialchars($formatter->format(new DateTime()));
                        } else {
                            echo htmlspecialchars(date('d M Y')); 
                        }
                        ?>
                        <small class="text-xs text-muted d-block fw-light text-capitalize">
                            <?= class_exists('IntlDateFormatter') ? htmlspecialchars((new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'eeee'))->format(new DateTime())) : htmlspecialchars($jour_semaine); ?>
                        </small>
                    </div>
                </div>
            </header>

            <div class="mb-4">
                <h4 class="fw-bold m-0">Bonjour <?= htmlspecialchars($user_name) ?> 👋</h4>
                <p class="text-muted m-0 text-sm">Voici la vue d'ensemble des opérations de votre municipalité.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-icon bg-total-light text-total"><i class="bi bi-megaphone-fill"></i></span>
                            <span class="text-total text-xs fw-semibold">+12%</span>
                        </div>
                        <small class="text-muted text-xs text-uppercase fw-semibold">Signalements totaux</small>
                        <h3 class="fw-bold m-0 mt-1"><?= intval($total_reports) ?></h3>
                        <div class="progress-mini mt-2 bg-total-light"><div class="progress-bar-mini bg-total" style="width: 100%"></div></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-icon bg-attente-light text-attente"><i class="bi bi-clock-history"></i></span>
                            <span class="text-attente text-xs fw-semibold">+5%</span>
                        </div>
                        <small class="text-muted text-xs text-uppercase fw-semibold">En attente</small>
                        <h3 class="fw-bold m-0 mt-1"><?= intval($attente_reports) ?></h3>
                        <div class="progress-mini mt-2 bg-attente-light"><div class="progress-bar-mini bg-attente" style="width: <?= $p_attente ?>%"></div></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-icon bg-encours-light text-encours"><i class="bi bi-play-circle"></i></span>
                            <span class="text-danger text-xs fw-semibold">-8%</span>
                        </div>
                        <small class="text-muted text-xs text-uppercase fw-semibold">En cours</small>
                        <h3 class="fw-bold m-0 mt-1"><?= intval($encours_reports) ?></h3>
                        <div class="progress-mini mt-2 bg-encours-light"><div class="progress-bar-mini bg-encours" style="width: <?= $p_encours ?>%"></div></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-icon bg-resolu-light text-resolu"><i class="bi bi-check-circle-fill"></i></span>
                            <span class="text-resolu text-xs fw-semibold">+18%</span>
                        </div>
                        <small class="text-muted text-xs text-uppercase fw-semibold">Résolus</small>
                        <h3 class="fw-bold m-0 mt-1"><?= intval($resolu_reports) ?></h3>
                        <div class="progress-mini mt-2 bg-resolu-light"><div class="progress-bar-mini bg-resolu" style="width: <?= $p_resolu ?>%"></div></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl">
                    <div class="card shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-icon bg-rejete-light text-rejete"><i class="bi bi-x-circle-fill"></i></span>
                            <span class="text-secondary text-xs fw-semibold">-2%</span>
                        </div>
                        <small class="text-muted text-xs text-uppercase fw-semibold">Rejetés</small>
                        <h3 class="fw-bold m-0 mt-1"><?= intval($rejete_reports) ?></h3>
                        <div class="progress-mini mt-2 bg-rejete-light"><div class="progress-bar-mini bg-rejete" style="width: <?= $p_rejete ?>%"></div></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card shadow-sm p-3 mb-4">
                        <h6 class="fw-bold mb-3">Signalements par statut</h6>
                        <div class="row align-items-center">
                            <div class="col-6 position-relative d-flex justify-content-center">
                                <canvas id="statusChart" style="max-height: 150px; max-width: 150px;"></canvas>
                                <div class="position-absolute text-center" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <h4 class="fw-bold m-0"><?= intval($total_reports) ?></h4>
                                    <small class="text-muted text-xs">Total</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled m-0 text-xs d-flex flex-column gap-2">
                                    <li><i class="bi bi-circle-fill text-attente me-2"></i> En attente <span class="fw-bold ms-2"><?= intval($attente_reports) ?> (<?= $p_attente ?>%)</span></li>
                                    <li><i class="bi bi-circle-fill text-encours me-2"></i> En cours <span class="fw-bold ms-2"><?= intval($encours_reports) ?> (<?= $p_encours ?>%)</span></li>
                                    <li><i class="bi bi-circle-fill text-resolu me-2"></i> Résolus <span class="fw-bold ms-2"><?= intval($resolu_reports) ?> (<?= $p_resolu ?>%)</span></li>
                                    <li><i class="bi bi-circle-fill text-rejete me-2"></i> Rejetés <span class="fw-bold ms-2"><?= intval($rejete_reports) ?> (<?= $p_rejete ?>%)</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Interventions du jour <small class="text-muted fw-normal d-block text-xs"><?= intval($total_interventions_du_jour) ?> programmée(s)</small></h6>
                            <a href="interventions.php" class="text-decoration-none text-sm fw-medium text-total">Voir tout</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <?php if (empty($interventions)): ?>
                                <p class="text-muted text-xs m-0 py-2">Aucune intervention planifiée pour aujourd'hui.</p>
                            <?php else: ?>
                                <?php foreach ($interventions as $interv): 
                                    $badge_class = ($interv['status'] == 'En cours') ? 'bg-encours-light text-encours' : 'bg-attente-light text-attente';
                                ?>
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-secondary-light shadow-sm" style="width:45px; height:45px; background: url('https://via.placeholder.com/45') center/cover;"></div>
                                        <div>
                                            <p class="m-0 fw-semibold text-sm"><?= htmlspecialchars($interv['title']) ?></p>
                                            <small class="text-muted text-xs">
                                                <i class="bi bi-geo-alt text-success"></i> <?= htmlspecialchars($interv['zone_name'] ?? 'Zone inconnue') ?> • 
                                                <i class="bi bi-clock"></i> <?= htmlspecialchars(date('H:i', strtotime($interv['start_time']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($interv['end_time']))) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <span class="badge <?= $badge_class ?> rounded-pill px-2 py-1 text-xs fw-medium"><?= htmlspecialchars($interv['status']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm p-3">
                        <h6 class="fw-bold mb-3">Activité récente</h6>
                        <div class="timeline d-flex flex-column gap-3 text-xs">
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-muted m-0">Aucun signalement récent.</p>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $act): 
                                    $icon_color = ($act['status'] == 'Résolu') ? 'text-resolu' : (($act['status'] == 'En cours') ? 'text-encours' : 'text-attente');
                                ?>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <i class="bi bi-circle-fill <?= $icon_color ?> me-2"></i> 
                                        <strong>Signalement #<?= intval($act['id']) ?> (<?= htmlspecialchars($act['status']) ?>)</strong> 
                                        <span class="text-muted d-block ms-4"><?= htmlspecialchars($act['cat_name'] ?? 'Non catégorisé') ?> à <?= htmlspecialchars($act['zone_name'] ?? 'Zone non spécifiée') ?></span>
                                    </div>
                                    <span class="text-muted" style="font-size:10px;"><?= htmlspecialchars(date('d M, H:i', strtotime($act['created_at']))) ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm p-3 mb-4">
                        <h6 class="fw-bold mb-3 text-dark">Signalements sur la carte (Kaolack)</h6>
                        <div id="map"></div>
                    </div>

                    <div class="card shadow-sm p-3 mb-4">
                        <h6 class="fw-bold mb-3">Performance de l'équipe</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><div class="p-2 border rounded bg-white"><small class="text-muted text-xs">Taux de résolution</small><h5 class="fw-bold text-success my-1">92%</h5><small class="text-success text-xs">+8%</small></div></div>
                            <div class="col-6"><div class="p-2 border rounded bg-white"><small class="text-muted text-xs">Temps d'interv.</small><h5 class="fw-bold text-dark my-1">2h 45m</h5><small class="text-success text-xs">+15m</small></div></div>
                        </div>
                    </div>

                    <div class="card shadow-sm p-3 mb-4">
                        <h6 class="fw-bold mb-3">Répartition par catégorie</h6>
                        <div class="d-flex flex-column gap-2 text-xs">
                            <?php if (empty($categories_dist)): ?>
                                <p class="text-muted m-0">Aucune catégorie disponible.</p>
                            <?php else: ?>
                                <?php foreach ($categories_dist as $cdist): 
                                    $p_cat = $total_reports > 0 ? round(($cdist['count_cat'] / $total_reports) * 100) : 0;
                                ?>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>📁 <?= htmlspecialchars($cdist['name']) ?></span>
                                        <span class="text-muted"><?= intval($cdist['count_cat']) ?> (<?= $p_cat ?>%)</span>
                                    </div>
                                    <div class="progress-mini bg-light"><div class="progress-bar-mini bg-total" style="width: <?= $p_cat ?>%"></div></div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm p-3">
                        <h6 class="fw-bold mb-3">Alertes importantes</h6>
                        <div class="d-flex flex-column gap-2">
                            <?php if ($alert_48h_count > 0): ?>
                            <div class="alert alert-danger p-2 border-0 rounded text-xs d-flex justify-content-between align-items-center m-0 shadow-sm">
                                <div><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i> <strong><?= intval($alert_48h_count) ?> signalement(s) critiques (+48h)</strong><span class="d-block text-muted ms-4">À traiter en priorité absolue.</span></div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div class="alert alert-warning p-2 border-0 rounded text-xs d-flex justify-content-between align-items-center m-0 shadow-sm">
                                <div><i class="bi bi-exclamation-circle-fill me-2 text-warning"></i> <strong>Vérification système</strong><span class="d-block text-muted ms-4">Aucun autre retard critique détecté.</span></div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=VOTRE_CLE_API_GOOGLE&callback=initMap" async defer></script>

    <script>
        // Initialisation de la carte Google Maps centrée sur Kaolack
        function initMap() {
            const kaolackCenter = { lat: 14.1438, lng: -16.0712 }; 
            
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 13,
                center: kaolackCenter,
                mapTypeControl: false,
                streetViewControl: true,
                styles: [
                    { "featureType": "poi", "elementType": "labels", "stylers": [{ "visibility": "off" }] }
                ]
            });

            // Marqueurs statiques fictifs situés à Kaolack
            const markers = [
                { coords: { lat: 14.1465, lng: -16.0750 }, title: "Ndangane - Encombrement Voie Publique" },
                { coords: { lat: 14.1390, lng: -16.0680 }, title: "Leona - Panne Éclairage" },
                { coords: { lat: 14.1510, lng: -16.0795 }, title: "Escale - Accumulation Déchets" }
            ];

            markers.forEach(markerInfo => {
                const marker = new google.maps.Marker({
                    position: markerInfo.coords,
                    map: map,
                    title: markerInfo.title,
                    animation: google.maps.Animation.DROP
                });

                const infowindow = new google.maps.InfoWindow({
                    content: `<div class="p-1 text-xs"><strong>${markerInfo.title}</strong></div>`
                });

                marker.addListener("click", () => {
                    infowindow.open({
                        anchor: marker,
                        map,
                    });
                });
            });
        }

        // Graphique Donut (Chart.js)
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['En attente', 'En cours', 'Résolus', 'Rejetés'],
                    datasets: [{
                        data: [
                            <?= intval($attente_reports) ?>, 
                            <?= intval($encours_reports) ?>, 
                            <?= intval($resolu_reports) ?>, 
                            <?= intval($rejete_reports) ?>
                        ],
                        backgroundColor: ['#ffb703', '#023e8a', '#00a651', '#e63946'],
                        borderWidth: 0
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    cutout: '80%'
                }
            });
        });
    </script>
</body>
</html>
