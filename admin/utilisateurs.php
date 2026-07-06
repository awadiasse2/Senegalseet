<?php
// admin/utilisateurs.php
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

// 2. TRAITEMENT DU FORMULAIRE : AJOUT NOUVEL UTILISATEUR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'Citoyen');

    if (!empty($name) && !empty($email) && !empty($password)) {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error_msg = "Cette adresse email est déjà utilisée.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$name, $email, $hashed_password, $role]);
                header('Location: utilisateurs.php?success=add');
                exit();
            }
        } catch (PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    } else {
        $error_msg = "Veuillez remplir tous les champs.";
    }
}

// 3. TRAITEMENT : SUPPRESSION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    if ($id_to_delete !== (int)$_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            header('Location: utilisateurs.php?success=delete');
            exit();
        } catch (PDOException $e) {
            $error_msg = "Erreur de suppression.";
        }
    }
}

// 4. RÉCUPÉRATION DES UTILISATEURS
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Gestion des Utilisateurs</title>
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

        /* Structure */
        main { margin-left: 270px; min-width: 0; width: 100%; }
        .top-bar-mobile { display: none; background: var(--sidebar); padding: 12px 16px; }
        .card-table-container { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--shadow); }

        /* Responsive */
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
                <li class="nav-item"><a href="utilisateurs.php" class="nav-link active"><i class="bi bi-people"></i> Utilisateurs</a></li>
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
                    <li class="nav-item"><a href="utilisateurs.php" class="nav-link active"><i class="bi bi-people"></i> Utilisateurs</a></li>
                    <li class="nav-item"><a href="municipalites.php" class="nav-link"><i class="bi bi-building"></i> Municipalités</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link"><i class="bi bi-tags"></i> Catégories</a></li>
                    <li class="nav-item"><a href="zones.php" class="nav-link"><i class="bi bi-geo-alt"></i> Zones</a></li>
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
                    <h4 class="fw-bold m-0">Gestion des Utilisateurs</h4>
                    <small class="text-muted">Gérer les accès des comptes citoyens et administratifs</small>
                </div>
                <button class="btn btn-success btn-sm px-3 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus me-1"></i> Ajouter un utilisateur
                </button>
            </header>

            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-3"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success shadow-sm border-0 mb-3">
                    <?= $_GET['success'] === 'add' ? "Utilisateur ajouté avec succès." : "Utilisateur supprimé avec succès." ?>
                </div>
            <?php endif; ?>

            <!-- CONTAINER DE TABLE COMPATIBLE TOUT ÉCRAN -->
            <div class="card-table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($users)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Aucun utilisateur trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($u['name']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php 
                                            $role_class = match(trim(strtolower($u['role']))) {
                                                'admin' => 'bg-danger bg-opacity-10 text-danger',
                                                'equipe' => 'bg-primary bg-opacity-10 text-primary',
                                                default => 'bg-secondary bg-opacity-10 text-muted'
                                            };
                                        ?>
                                        <span class="badge <?= $role_class ?> rounded-pill px-2.5 py-1" style="font-size: 10px; font-weight: 700;">
                                            <?= htmlspecialchars($u['role']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <a href="utilisateurs.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-light text-danger rounded-circle p-1" style="width:28px; height:28px;" onclick="return confirm('Voulez-vous supprimer cet utilisateur ?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Moi</span>
                                        <?php endif; ?>
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

    <!-- MODAL AJOUT UTILISATEUR COMPLET -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h6 class="modal-title fw-bold m-0"><i class="bi bi-person-plus me-2 text-success"></i>Nouvel Utilisateur</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <form action="utilisateurs.php" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Nom Complet</label>
                            <input type="text" name="name" class="form-control form-control-sm rounded-3 shadow-none border" required placeholder="Ex: Ousmane Niang">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Adresse Email</label>
                            <input type="email" name="email" class="form-control form-control-sm rounded-3 shadow-none border" required placeholder="nom@exemple.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Mot de passe</label>
                            <input type="password" name="password" class="form-control form-control-sm rounded-3 shadow-none border" required placeholder="••••••••">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold">Rôle Système</label>
                            <select name="role" class="form-select form-select-sm rounded-3 shadow-none border">
                                <option value="Citoyen">Citoyen</option>
                                <option value="Equipe">Équipe Terrain</option>
                                <option value="Admin">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-sm btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_user" class="btn btn-sm btn-success rounded-3 px-3 shadow-sm">Créer le compte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>