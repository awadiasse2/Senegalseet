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

// Récupération dynamique des signalements géolocalisés
$stmt = $pdo->query("SELECT r.latitude, r.longitude, r.status, c.name as cat_name, z.name as zone_name 
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN zones z ON r.zone_id = z.id
    WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL");
$reports_list = $stmt->fetchAll();

$markers_data = [];
foreach ($reports_list as $rep) {
    $markers_data[] = [
        'coords' => [
            'lat' => (float)$rep['latitude'],
            'lng' => (float)$rep['longitude']
        ],
        'title'  => "Signalement : " . ($rep['cat_name'] ?? 'Inconnu'),
        'status' => $rep['status'],
        'zone'   => $rep['zone_name'] ?? 'Zone non spécifiée'
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Interactive — SenegalSet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }
        
        /* Structure globale */
        .app-container {
            width: 100%;
            max-width: 100vw;
            min-height: 100vh;
            display: flex !important;
        }

        /* Forcer la Sidebar Desktop à garder ses propriétés */
        .sidebar-desktop {
            width: 260px !important;
            min-width: 260px !important;
            max-width: 260px !important;
            min-height: 100vh;
        }

        /* Neutralisation des anciens styles conflictuels de style.css */
        .main-content {
            flex-grow: 1 !important;
            min-width: 0 !important;
            width: 100% !important;
            margin-left: 0 !important; /* Annule l'espace blanc fantôme */
            padding-left: 1.5rem !important;
        }

        /* Ajustement de la carte Leaflet */
        #map { 
            height: 60vh; 
            width: 100% !important; 
            max-width: 100%;
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            z-index: 1; 
        }

        @media (min-width: 992px) {
            #map { height: calc(100vh - 140px); }
        }
        
        .header-avatar { object-fit: cover; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar sidebar-desktop p-3 d-none d-lg-flex flex-column justify-content-between">
            <div>
                <div class="brand mb-4 d-flex align-items-center gap-2">
                    <div class="logo-placeholder rounded-circle p-2 text-white fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">SS</div>
                    <div>
                        <h6 class="m-0 fw-bold tracking-wide text-uppercase text-white">SenegalSet</h6>
                        <small class="text-sidebar-muted text-xs">Mon espace client</small>
                    </div>
                </div>
                <div class="menu-section mb-4">
                    <small class="text-uppercase text-sidebar-muted text-xs fw-bold tracking-wider d-block mb-2">Menu client</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item"><a href="espace_client.php" class="nav-link"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
                        <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone me-2"></i> Mes signalements</a></li>
                        <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools me-2"></i> Interventions</a></li>
                        <li class="nav-item"><a href="calendrier.php" class="nav-link"><i class="bi bi-calendar3 me-2"></i> Calendrier</a></li>
                        <li class="nav-item"><a href="rapports.php" class="nav-link"><i class="bi bi-file-earmark-bar-graph me-2"></i> Rapports</a></li>
                        <li class="nav-item"><a href="carte.php" class="nav-link active rounded"><i class="bi bi-map me-2"></i> Carte</a></li>
                    </ul>
                </div>
            </div>
            <div class="sidebar-footer border-top border-secondary pt-3">
                <a href="../auth/logout.php" class="text-danger text-decoration-none text-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-left"></i> Déconnexion
                </a>
            </div>
        </aside>

        <div class="offcanvas offcanvas-start sidebar p-3" tabindex="-1" id="sidebarMobile" aria-labelledby="sidebarMobileLabel" style="width: 280px;">
            <div class="offcanvas-header p-0 mb-4 justify-content-between align-items-center">
                <div class="brand d-flex align-items-center gap-2">
                    <div class="logo-placeholder rounded-circle p-2 text-white fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">SS</div>
                    <div>
                        <h6 class="m-0 fw-bold tracking-wide text-uppercase text-white">SenegalSet</h6>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column justify-content-between">
                <div class="menu-section mb-4">
                    <small class="text-uppercase text-sidebar-muted text-xs fw-bold tracking-wider d-block mb-2">Menu client</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item"><a href="espace_client.php" class="nav-link"><i class="bi bi-grid-1x2-fill me-2"></i> Tableau de bord</a></li>
                        <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone me-2"></i> Mes signalements</a></li>
                        <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools me-2"></i> Interventions</a></li>
                        <li class="nav-item"><a href="calendrier.php" class="nav-link"><i class="bi bi-calendar3 me-2"></i> Calendrier</a></li>
                        <li class="nav-item"><a href="rapports.php" class="nav-link"><i class="bi bi-file-earmark-bar-graph me-2"></i> Rapports</a></li>
                        <li class="nav-item"><a href="carte.php" class="nav-link active rounded"><i class="bi bi-map me-2"></i> Carte</a></li>
                    </ul>
                </div>
                <div class="sidebar-footer border-top border-secondary pt-3">
                    <a href="../auth/logout.php" class="text-danger text-decoration-none text-sm fw-medium d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-left"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>

        <main class="main-content p-3 p-md-4">
            <header class="d-flex justify-content-between align-items-center mb-4 gap-2">
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <button class="btn p-0 border-0 fs-4 text-dark d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile" aria-controls="sidebarMobile">
                        <i class="bi bi-list"></i>
                    </button>
                    <button class="btn p-0 border-0 fs-4 text-dark d-none d-lg-inline"><i class="bi bi-list"></i></button>
                    <h4 class="m-0 fw-bold fs-5 fs-md-4">Anomalies géolocalisées</h4>
                </div>
                
                <div class="d-flex align-items-center gap-2 bg-white p-1 pe-2 pe-md-3 border rounded-4 shadow-sm">
                    <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle header-avatar" width="35" height="35" alt="Avatar">
                    <div class="d-none d-sm-block">
                        <p class="m-0 fw-bold text-dark" style="font-size: 12px; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($user_name) ?></p>
                        <small class="text-muted d-block" style="font-size: 9px; text-transform: capitalize;">Espace <?= htmlspecialchars($user_role) ?></small>
                    </div>
                </div>
            </header>

            <div id="map"></div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const map = L.map('map').setView([14.6937, -17.4474], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const markersData = <?= json_encode($markers_data) ?>;
            const group = [];

            if (markersData.length > 0) {
                markersData.forEach(item => {
                    if (item.coords.lat && item.coords.lng) {
                        const marker = L.marker([item.coords.lat, item.coords.lng]).addTo(map);
                        
                        const popupContent = `
                            <div class="info-window-content">
                                <h6 style="margin: 0 0 4px 0; font-weight: 600; color: #333; font-size: 13px;">\${item.title}</h6>
                                <p style="margin: 0 0 6px 0; font-size: 11px; color: #666;"><strong>Secteur :</strong> \${item.zone}</p>
                                <span style="font-size: 10px; font-weight: 500; background: #e2e8f0; padding: 2px 8px; border-radius: 10px; color: #4a5568; display: inline-block;">
                                    Statut : &nbsp;\${item.status}
                                </span>
                            </div>
                        `;
                        marker.bindPopup(popupContent);
                        group.push([item.coords.lat, item.coords.lng]);
                    }
                });

                if (group.length > 1) {
                    map.fitBounds(group);
                } else if (group.length === 1) {
                    map.setView(group[0], 14);
                }
            }

            setTimeout(() => {
                map.invalidateSize();
            }, 300);
        });
    </script>
</body>
</html>