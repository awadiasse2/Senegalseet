<?php
// admin/zones.php
session_start();

// 1. SÉCURITÉ : Connexion et rôle Admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
$user_role = trim(strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? ''));
if ($user_role !== 'admin') {
    header('Location: ../client/espace_client.php');
    exit();
}

require_once '../config/config.php';

// Variables Administrateur connecté
$admin_name = $_SESSION['user_name'] ?? 'Admin Sénégalset';
$admin_avatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80';

$error_msg = "";
$success_msg = "";

// 2. TRAITEMENT DU FORMULAIRE : AJOUT D'UNE ZONE / QUARTIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subzone'])) {
    $name = trim($_POST['name'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    if (!empty($name)) {
        try {
            // Vérification des doublons dans la table zones
            $check = $pdo->prepare("SELECT id FROM zones WHERE name = ?");
            $check->execute([$name]);
            if ($check->fetch()) {
                $error_msg = "Cette zone ou quartier existe déjà.";
            } else {
                $ins = $pdo->prepare("INSERT INTO zones (name, latitude, longitude) VALUES (?, ?, ?)");
                $ins->execute([$name, $latitude, $longitude]);
                
                header('Location: zones.php?success=add');
                exit();
            }
        } catch (PDOException $e) {
            $error_msg = "Erreur lors de l'enregistrement de la zone : " . $e->getMessage();
        }
    } else {
        $error_msg = "Le nom du secteur/quartier est obligatoire.";
    }
}

// 3. TRAITEMENT : SUPPRESSION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM zones WHERE id = ?");
        $stmt->execute([$id_to_delete]);
        header('Location: zones.php?success=delete');
        exit();
    } catch (PDOException $e) {
        $error_msg = "Impossible de supprimer cette zone car elle contient des signalements actifs.";
    }
}

// Redirection et gestion des alertes succès
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'delete') $success_msg = "Secteur supprimé avec succès.";
    if ($_GET['success'] === 'add') $success_msg = "Nouveau secteur enregistré avec succès !";
}

// 4. RÉCUPÉRATION DE TOUTES LES ZONES AVEC LEUR COMPTE DE RECOUVREMENT
try {
    $query = "SELECT z.*, COUNT(r.id) as total_reports 
              FROM zones z 
              LEFT JOIN reports r ON r.zone_id = z.id 
              GROUP BY z.id 
              ORDER BY z.name ASC";
    $sub_zones = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sub_zones = [];
    $error_msg = "Erreur lors de la récupération des données de secteurs.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Cartographie des Zones</title>
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
            --shadow: 0 10px 25px rgba(2, 6, 23, .06);
            --border: rgba(2, 6, 23, .06);
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg); 
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
            position: fixed; top: 0; left: 0; z-index: 100;
            overflow-y: auto;
        }
        .brand-logo {
            width: 28px; height: 28px;
            border-radius: 10px;
            background: rgba(0,166,81,.15);
            display: flex; align-items: center; justify-content: center;
            color: var(--green); font-weight: 800;
        }
        .sidebar-nav .nav-link { 
            color: rgba(148,163,184,.95); 
            padding: 10px 12px; 
            border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; transition: .15s ease;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active { color: #fff; background: rgba(0,166,81,.22); }
        .sidebar-title { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: rgba(148,163,184,.85); margin: 18px 10px 10px; }

        /* Structure adaptative */
        main { margin-left: 270px; min-width: 0; width: 100%; }
        .top-bar-mobile { display: none; background: var(--sidebar); padding: 12px 16px; }
        .card-table-container { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--shadow); }

        /* Breakpoints Responsive */
        @media (max-width: 991.98px) {
            .sidebar-desktop { display: none !important; }
            main { margin-left: 0 !important; padding: 16px !important; }
            .top-bar-mobile { display: flex; }
        }
    </style>
</head>
<body>

    <!-- TOP BAR MOBILE -->
    <div class="top-bar-mobile align-items-center justify-content-between w-100 d-lg-none shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-logo text-white bg-success bg-opacity-25">S</div>
            <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
        </div>
        <button class="btn btn-success btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            <i class="bi bi-list fs-5"></i>
        </button>
    </div>

    <!-- TIROIR OFF CANVAS MOBILE -->
    <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" style="background: var(--sidebar); width: 270px;">
        <div class="offcanvas-header border-bottom border-secondary border-opacity-20">
            <div class="d-flex align-items-center gap-2">
                <div class="brand-logo">S</div>
                <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body sidebar-nav p-3" style="overflow-y: auto;">
            <ul class="nav flex-column mb-2">
                <li class="nav-item"><a href="Dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Tableau de bord</a></li>
            </ul>
            <div class="sidebar-title">Gestion de la plateforme</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item"><a href="utilisateurs.php" class="nav-link"><i class="bi bi-people"></i> Utilisateurs</a></li>
                <li class="nav-item"><a href="municipalites.php" class="nav-link"><i class="bi bi-building"></i> Municipalités</a></li>
                <li class="nav-item"><a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Catégories</a></li>
                <li class="nav-item"><a href="zones.php" class="nav-link active"><i class="bi bi-geo-alt"></i> Zones</a></li>
                <li class="nav-item"><a href="equipes.php" class="nav-link"><i class="bi bi-shield"></i> Équipes</a></li>
                <li class="nav-item"><a href="parametres.php" class="nav-link"><i class="bi bi-gear"></i> Paramètres</a></li>
            </ul>
            <div class="sidebar-title">Opérations</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone"></i> Signalements</a></li>
                <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools"></i> Interventions</a></li>
            </ul>
            <div class="pt-4 border-top border-secondary border-opacity-40">
                <a href="../auth/logout.php" class="nav-link text-danger px-0"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="d-flex w-100">

        <!-- SIDEBAR DE BUREAU -->
        <aside class="sidebar-desktop sidebar-nav d-none d-lg-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-4 px-2">
                    <div class="brand-logo">S</div>
                    <div>
                        <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
                    </div>
                </div>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item"><a href="Dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Tableau de bord</a></li>
                </ul>
                <div class="sidebar-title">Gestion de la plateforme</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item"><a href="utilisateurs.php" class="nav-link"><i class="bi bi-people"></i> Utilisateurs</a></li>
                    <li class="nav-item"><a href="municipalites.php" class="nav-link"><i class="bi bi-building"></i> Municipalités</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Catégories</a></li>
                    <li class="nav-item"><a href="zones.php" class="nav-link active"><i class="bi bi-geo-alt"></i> Zones</a></li>
                    <li class="nav-item"><a href="equipes.php" class="nav-link"><i class="bi bi-shield"></i> Équipes</a></li>
                </ul>
                <div class="sidebar-title">Opérations</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone"></i> Signalements</a></li>
                    <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools"></i> Interventions</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link text-danger mt-3"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                </ul>
            </div>
        </aside>

        <!-- ZONE DE CONTENU CENTRAL -->
        <main class="p-3 p-md-4">
            <header class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
                <div>
                    <h4 class="fw-bold m-0 text-dark">Secteurs & Zones d'Intervention</h4>
                    <small class="text-muted">Découpage fin de la cartographie des anomalies urbaines</small>
                </div>
                <button type="button" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubZoneModal">
                    <i class="bi bi-geo-fill me-1"></i> Nouveau Secteur
                </button>
            </header>

            <!-- NOTIFICATIONS ET MESSAGES RETOURS -->
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-3"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success shadow-sm border-0 mb-3"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>

            <!-- TABLEAU ADAPTATIF -->
            <div class="card-table-container">
                <div class="h6 fw-bold mb-3" style="font-size:0.85rem;">Cartographie des secteurs (<?= count($sub_zones) ?>)</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>ID</th>
                                <th>Nom du Secteur / Quartier</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th class="text-center">Signalements</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($sub_zones)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Aucun sous-secteur configuré.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sub_zones as $sz): ?>
                                <tr>
                                    <td class="text-muted">#<?= $sz['id'] ?></td>
                                    <td class="fw-bold text-dark"><i class="bi bi-geo text-muted me-2"></i><?= htmlspecialchars($sz['name']) ?></td>
                                    <td class="text-muted"><?= $sz['latitude'] ?? 'Non renseignée' ?></td>
                                    <td class="text-muted"><?= $sz['longitude'] ?? 'Non renseignée' ?></td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-dark bg-opacity-10 text-dark px-3 py-1fw-bold" style="font-size: 10px;">
                                            <?= (int)$sz['total_reports'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="zones.php?action=delete&id=<?= $sz['id'] ?>" class="btn btn-sm btn-light text-danger rounded-circle p-1" style="width:28px; height:28px;" onclick="return confirm('Confirmez-vous la suppression définitive de ce secteur géographique ?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL AJOUT ZONE / SECTEUR -->
    <div class="modal fade" id="addSubZoneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h6 class="modal-title fw-bold m-0"><i class="bi bi-geo-alt-fill text-success me-2"></i>Ajouter un secteur ou quartier</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <form action="zones.php" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Nom du Secteur / Quartier *</label>
                            <input type="text" name="name" class="form-control form-control-sm rounded-3 shadow-none border" placeholder="Ex: Médina, Ndorong, Niodor..." required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label text-muted small fw-bold">Latitude (Optionnel)</label>
                                <input type="number" step="any" name="latitude" class="form-control form-control-sm rounded-3 shadow-none border" placeholder="Ex: 14.124">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label text-muted small fw-bold">Longitude (Optionnel)</label>
                                <input type="number" step="any" name="longitude" class="form-control form-control-sm rounded-3 shadow-none border" placeholder="Ex: -16.084">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_subzone" class="btn btn-sm btn-success rounded-3 px-3 shadow-sm">Enregistrer la zone</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>