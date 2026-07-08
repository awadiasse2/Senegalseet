<?php
// agent/carte.php
session_start();

// Protection de la page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    // header('Location: ../auth/login.php');
    // exit();
}

require_once '../config/config.php';

$agent_id = $_SESSION['user_id'] ?? 8; // ID de test par défaut (Ousmane Niang - Agent)
$error_msg = "";

try {
    // Récupération des interventions géolocalisées actives
    $query = "SELECT i.id, i.title as intervention_title, i.status, 
                     r.description as report_desc, r.latitude, r.longitude
              FROM interventions i
              INNER JOIN reports r ON i.report_id = r.id
              WHERE i.agent_id = :agent_id 
                AND r.latitude IS NOT NULL 
                AND r.longitude IS NOT NULL
                AND i.status IN ('En cours', 'À venir')";
              
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':agent_id', $agent_id, PDO::PARAM_INT);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Erreur lors du chargement de la carte : " . $e->getMessage();
    $locations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Carte Réseau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

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
            min-height: 100vh;
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
            height: 100vh;
            display: flex;
            flex-direction: column;
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
        
        /* CONTENEUR DE LA CARTE */
        .map-container {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 12px;
            flex-grow: 1;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }
        #map {
            width: 100%;
            height: 100%;
            min-height: 450px;
            border-radius: 12px;
            z-index: 1;
        }

        /* Personnalisation moderne des Popups Leaflet */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            padding: 4px;
        }
        .leaflet-popup-tip {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
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
                height: calc(100vh - 65px);
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
        <a href="index.php" class="nav-menu-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="interventions.php" class="nav-menu-item"><i class="bi bi-tools"></i> Interventions</a>
        <a href="carte.php" class="nav-menu-item active"><i class="bi bi-map"></i> Carte Terrain</a>
        <a href="profil.php" class="nav-menu-item"><i class="bi bi-person"></i> Mon Profil</a>
    </div>

    <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="../auth/logout.php" class="nav-menu-item text-danger border-top pt-3 rounded-0 m-0" onclick="return confirm('Se déconnecter ?')">
            <i class="bi bi-box-arrow-left"></i> Déconnexion
        </a>
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
        <a href="index.php" class="nav-menu-item"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="interventions.php" class="nav-menu-item"><i class="bi bi-tools"></i> Interventions</a>
        <a href="carte.php" class="nav-menu-item active"><i class="bi bi-map"></i> Carte Terrain</a>
        <a href="profil.php" class="nav-menu-item"><i class="bi bi-person"></i> Mon Profil</a>
    </div>
</div>

<!-- TOP BAR UNIQUE MOBILE -->
<div class="top-bar-mobile justify-content-between align-items-center">
    <button class="menu-toggle-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
        <i class="bi bi-list fs-3"></i>
    </button>
    <h6 class="m-0 fw-bold">Carte Réseau</h6>
    <div>
        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size: 11px;">GPS Actif</span>
    </div>
</div>

<!-- ZONE DE CONTENU PRINCIPALE -->
<div class="main-content">
    
    <!-- EN-TÊTE -->
    <div class="d-none d-lg-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold text-dark m-0">Carte des interventions</h3>
            <p class="text-muted m-0">Visualisation cartographique en temps réel de vos tâches assignées</p>
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger rounded-3 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_msg ?>
        </div>
    <?php endif; ?>

    <!-- ZONE DE LA CARTE -->
    <div class="map-container">
        <div id="map"></div>
    </div>

</div>

<!-- BARRE DE NAVIGATION MOBILE BASSE -->
<nav class="navbar bottom-nav fixed-bottom py-2 shadow-lg">
    <div class="container-fluid d-flex justify-content-around">
        <a href="index.php" class="nav-link-custom"><i class="bi bi-grid mb-1 fs-5"></i>Dashboard</a>
        <a href="interventions.php" class="nav-link-custom"><i class="bi bi-tools mb-1 fs-5"></i>Missions</a>
        <a href="carte.php" class="nav-link-custom active"><i class="bi bi-map mb-1 fs-5"></i>Carte</a>
        <a href="profil.php" class="nav-link-custom"><i class="bi bi-person mb-1 fs-5"></i>Profil</a>
    </div>
</nav>

<!-- Scripts JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. Initialisation de la carte (Centrée sur Kaolack/Sénégal par défaut)
    var map = L.map('map').setView([14.1324, -16.0740], 12);

    // 2. Chargement du fond de carte OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // 3. Récupération des données converties en JSON
    var locationsData = <?= json_encode($locations) ?>;
    var markersBounds = [];

    // 4. Injection des marqueurs
    if (locationsData.length > 0) {
        locationsData.forEach(function(item) {
            var lat = parseFloat(item.latitude);
            var lng = parseFloat(item.longitude);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                // Style dynamique du badge selon le statut
                var badgeClass = (item.status === 'En cours') ? 'bg-warning text-dark' : 'bg-primary text-white';
                
                var popupContent = `
                    <div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; width: 220px; padding: 4px 2px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge ${badgeClass} rounded-pill" style="font-size: 10px; padding: 4px 8px;">${item.status}</span>
                            <span class="text-muted fw-bold">#${item.id}</span>
                        </div>
                        <h6 style="margin: 0 0 4px 0; font-weight: 700; color: #1e293b; font-size: 13px;">${item.intervention_title}</h6>
                        <p style="margin: 0 0 12px 0; color: #64748b; line-height: 1.4; font-size: 11.5px;">${item.report_desc || 'Aucun descriptif disponible.'}</p>
                        <a href="action_intervention.php?id=${item.id}" class="btn btn-success text-white btn-sm w-100 d-block text-center shadow-sm py-1.5" style="font-size: 11px; border-radius: 8px; font-weight: 500; text-decoration: none; background-color: var(--emerald); border: none;">
                            Gérer l'intervention <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                `;

                var marker = L.marker([lat, lng]).addTo(map).bindPopup(popupContent);
                markersBounds.push([lat, lng]);
            }
        });

        // Ajustement automatique du zoom pour englober tous les repères
        if (markersBounds.length > 0) {
            map.fitBounds(markersBounds, { padding: [50, 50] });
        }
    }
</script>
</body>
</html>