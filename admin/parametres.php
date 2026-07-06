<?php
// admin/parametres.php
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

$settings = [
    'site_name' => 'SENEGALSET',
    'contact_email' => 'contact@senegalset.sn',
    'maintenance_mode' => '0',
    'max_upload_size' => '5'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings['site_name'] = trim($_POST['site_name'] ?? 'SENEGALSET');
    $settings['contact_email'] = trim($_POST['contact_email'] ?? '');
    $settings['max_upload_size'] = trim($_POST['max_upload_size'] ?? '5');
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? '1' : '0';
    $success_msg = "Les configurations système ont été mises à jour avec succès.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Configuration Système</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar: #06152d; --bg: #f5f7fb; --card: #ffffff; --text: #0f172a;
            --muted: #64748b; --green: #00a651; --shadow: 0 10px 25px rgba(2, 6, 23, .06); --border: rgba(2, 6, 23, .06);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg); color: var(--text); font-size: 0.82rem; overflow-x: hidden; }
        
        .wrapper { display: flex; width: 100%; }
        aside { 
            width: 270px; height: 100vh; background: var(--sidebar); padding: 18px 14px;
            position: fixed; top: 0; left: 0; z-index: 100; overflow-y: auto; transition: all 0.3s ease;
        }
        main { margin-left: 270px; min-width: 0; width: calc(100% - 270px); padding: 24px; transition: all 0.3s ease; }
        
        .brand-logo {
            width: 28px; height: 28px; border-radius: 10px; background: rgba(0,166,81,.15);
            display: flex; align-items: center; justify-content: center; color: var(--green); font-weight: 800;
        }
        .sidebar-nav .nav-link { 
            color: rgba(148,163,184,.95); padding: 10px 12px; border-radius: 12px;
            display: flex; align-items: center; gap: 10px; text-decoration: none; transition: .15s ease;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active { color: #fff; background: rgba(0,166,81,.22); }
        .sidebar-title { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: rgba(148,163,184,.85); margin: 18px 10px 10px; }
        .card-table-container { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 25px; box-shadow: var(--shadow); }

        .menu-toggle {
            display: none; position: fixed; top: 15px; right: 15px; z-index: 110;
            background: var(--green); color: white; border: none; padding: 8px 12px; border-radius: 8px;
        }

        @media (max-width: 991.98px) {
            .menu-toggle { display: block; }
            aside { left: -270px; }
            aside.active { left: 0; }
            main { margin-left: 0; width: 100%; padding: 60px 15px 20px 15px; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="mobileMenuBtn"><i class="bi bi-list fs-5"></i></button>

    <div class="wrapper">
        <aside class="sidebar-nav">
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <div class="brand-logo">S</div>
                <h6 class="m-0 fw-bold text-white">SENEGALSET</h6>
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
                <li class="nav-item"><a href="equipes.php" class="nav-link"><i class="bi bi-shield"></i> Équipes</a></li>
                <li class="nav-item"><a href="parametres.php" class="nav-link active"><i class="bi bi-gear"></i> Paramètres</a></li>
            </ul>
            <div class="sidebar-title">Opérations</div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="signalements.php" class="nav-link"><i class="bi bi-megaphone"></i> Signalements</a></li>
                <li class="nav-item"><a href="interventions.php" class="nav-link"><i class="bi bi-tools"></i> Interventions</a></li>
                <li class="nav-item"><a href="../auth/logout.php" class="nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
            </ul>
        </aside>

        <main>
            <header class="mb-4">
                <h4 class="fw-bold m-0 text-dark">Paramètres du Système</h4>
                <small class="text-muted">Ajuster les préférences générales et techniques de la plateforme</small>
            </header>

            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-3"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success shadow-sm border-0 mb-3"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>

            <div class="card-table-container">
                <form action="parametres.php" method="POST">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark"><i class="bi bi-sliders me-2 text-success"></i>Préférences Générales</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Nom de la Plateforme</label>
                            <input type="text" name="site_name" class="form-control form-control-sm rounded-3 shadow-none border" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Email de contact officiel</label>
                            <input type="email" name="contact_email" class="form-control form-control-sm rounded-3 shadow-none border" value="<?= htmlspecialchars($settings['contact_email']) ?>" required>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 mt-3 pb-2 border-bottom text-dark"><i class="bi bi-hdd-network me-2 text-success"></i>Fichiers & Stockage</h6>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted small fw-bold">Taille max des photos citoyens (Mo)</label>
                            <input type="number" name="max_upload_size" class="form-control form-control-sm rounded-3 shadow-none border" value="<?= htmlspecialchars($settings['max_upload_size']) ?>" required>
                            <small class="text-muted" style="font-size:10px;">Recommandé : 5 Mo maximum pour éviter d'encombrer le serveur.</small>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-4 pb-2 border-bottom text-danger"><i class="bi bi-exclamation-octagon me-2"></i>Maintenance & Sécurité</h6>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch p-0 ps-5 fs-6">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" <?= $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark" for="maintenanceMode" style="font-size: 0.85rem;">Activer le mode maintenance</label>
                        </div>
                        <small class="text-muted d-block mt-1 ps-5" style="font-size:10px;">
                            Si coché, l'accès à l'application web et mobile sera temporairement suspendu pour les citoyens afin d'effectuer des mises à jour de base de données.
                        </small>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-end">
                        <button type="submit" name="update_settings" class="btn btn-sm btn-success border-0 px-4" style="background-color: var(--emerald);">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Sauvegarder les modifications
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.querySelector('aside');
        if(menuBtn && sidebar) {
            menuBtn.addEventListener('click', function(e) {
                sidebar.toggleAttribute;
                sidebar.classList.toggle('active');
                e.stopPropagation();
            });
            document.addEventListener('click', function(e) {
                if(!sidebar.contains(e.target) && e.target !== menuBtn) {
                    sidebar.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>