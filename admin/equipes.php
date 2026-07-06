<?php
// admin/equipes.php
session_start();
require_once '../config/config.php';

// --- SÉCURITÉ ---
if (!isset($_SESSION['user_id']) || (trim(strtolower($_SESSION['user_role'] ?? '')) !== 'admin')) {
    header('Location: ../auth/login.php');
    exit();
}

// Variables Administrateur connecté
$admin_name = $_SESSION['user_name'] ?? 'Admin Sénégalset';
$admin_avatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80';

$error_msg = "";
$success_msg = "";

// --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $name = trim($_POST['name']);
    $zone_id = !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : null;
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    $status = $_POST['status'] ?? 'Disponible';

    try {
        $pdo->beginTransaction();
        
        // 1. Création de l'équipe
        $stmt = $pdo->prepare("INSERT INTO teams (name, zone_id, status, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $zone_id, $status]);
        $new_team_id = $pdo->lastInsertId();

        // 2. Affectation de l'agent (Mise à jour de la table users)
        if ($agent_id) {
            $stmt_agent = $pdo->prepare("UPDATE users SET team_id = ? WHERE id = ?");
            $stmt_agent->execute([$new_team_id, $agent_id]);
        }
        
        $pdo->commit();
        header('Location: equipes.php?success=1');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_msg = "Erreur SQL : " . $e->getMessage();
    }
}

// Gestion des alertes succès après redirection
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_msg = "Nouvelle brigade d'intervention créée avec succès !";
}

// --- RÉCUPÉRATION DES DONNÉES ---
try {
    $zones = $pdo->query("SELECT id, name FROM zones ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $agents = $pdo->query("SELECT id, name FROM users WHERE role IN ('agent', 'technicien')")->fetchAll(PDO::FETCH_ASSOC);

    // Jointure pour afficher les équipes et les noms des agents associés
    $query = "SELECT t.*, z.name as zone_name, GROUP_CONCAT(u.name SEPARATOR ', ') as agents_list 
              FROM teams t 
              LEFT JOIN zones z ON t.zone_id = z.id 
              LEFT JOIN users u ON t.id = u.team_id 
              GROUP BY t.id ORDER BY t.created_at DESC";
    $teams = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $zones = [];
    $agents = [];
    $teams = [];
    $error_msg = "Erreur lors du chargement des données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Gestion des Équipes</title>
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
                <li class="nav-item"><a href="zones.php" class="nav-link"><i class="bi bi-geo-alt"></i> Zones</a></li>
                <li class="nav-item"><a href="equipes.php" class="nav-link active"><i class="bi bi-shield"></i> Équipes</a></li>
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
                    <li class="nav-item"><a href="zones.php" class="nav-link"><i class="bi bi-geo-alt"></i> Zones</a></li>
                    <li class="nav-item"><a href="equipes.php" class="nav-link active"><i class="bi bi-shield"></i> Équipes</a></li>
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
                    <h4 class="fw-bold m-0 text-dark">Brigades d'intervention</h4>
                    <small class="text-muted">Gérez les équipes d'intervention sur le terrain</small>
                </div>
                <button type="button" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                    <i class="bi bi-plus-circle me-1"></i> Nouvelle Équipe
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Nom Équipe</th>
                                <th>Zone d'affectation</th>
                                <th>Agents Affectés</th>
                                <th class="text-end">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($teams)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Aucune brigade configurée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($teams as $t): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><i class="bi bi-shield-shaded text-success me-2"></i><?= htmlspecialchars($t['name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($t['zone_name'] ?? 'Non assignée') ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($t['agents_list'] ?? 'Aucun agent') ?></td>
                                    <td class="text-end">
                                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3 py-1 fw-bold" style="font-size: 10px;">
                                            <?= htmlspecialchars($t['status']) ?>
                                        </span>
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

    <!-- MODAL AJOUT ÉQUIPE -->
    <div class="modal fade" id="addTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h6 class="modal-title fw-bold m-0"><i class="bi bi-shield-plus text-success me-2"></i>Créer une brigade</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <form action="equipes.php" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Nom de la brigade *</label>
                            <input type="text" name="name" class="form-control form-control-sm rounded-3 shadow-none border" placeholder="Ex: Brigade Centre, Équipe Propreté..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Zone d'intervention principale</label>
                            <select name="zone_id" class="form-select form-select-sm rounded-3 shadow-none border">
                                <option value="">-- Choisir une zone --</option>
                                <?php foreach($zones as $z): ?>
                                    <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Affecter un premier agent</label>
                            <select name="agent_id" class="form-select form-select-sm rounded-3 shadow-none border">
                                <option value="">-- Choisir un agent / technicien --</option>
                                <?php foreach($agents as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label text-muted small fw-bold">Statut initial</label>
                            <select name="status" class="form-select form-select-sm rounded-3 shadow-none border">
                                <option value="Disponible">Disponible</option>
                                <option value="En intervention">En intervention</option>
                                <option value="Inactif">Inactif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_team" class="btn btn-sm btn-success rounded-3 px-3 shadow-sm">Créer l'équipe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>