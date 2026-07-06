<?php
// admin/signalements.php
session_start();

// 1. SÉCURITÉ : Connexion et rôle Admin
if (!isset($_SESSION['user_id']) || trim(strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';

// 2. ACTIONS : MISE À JOUR STATUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_report_status') {
    $report_id = (int)($_POST['report_id'] ?? 0);
    $new_status = trim((string)($_POST['status'] ?? ''));
    if ($report_id > 0 && !empty($new_status)) {
        $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $report_id]);
    }
}

// 3. RÉCUPÉRATION DES SIGNALEMENTS
$query = "SELECT r.id, r.status, r.created_at, r.description, r.image,
                 z.name as zone_name, c.name as category_name, u.name as user_name
          FROM reports r
          LEFT JOIN zones z ON r.zone_id = z.id
          LEFT JOIN categories c ON r.category_id = c.id
          LEFT JOIN users u ON r.user_id = u.id
          ORDER BY r.created_at DESC";
$reports = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signalements - SENEGALSET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --bg-sidebar: #06152d; --emerald: #00a651; --bg-body: #f8fafc; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-body); overflow-x: hidden; }
        
        /* Layout Global */
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        .sidebar { width: 260px; min-width: 260px; background: var(--bg-sidebar); position: fixed; height: 100vh; z-index: 1000; transition: left 0.3s ease; top: 0; }
        main { margin-left: 260px; width: calc(100% - 260px); padding: 25px; transition: margin 0.3s ease; }

        /* Styles Tableau & Cartes */
        .table-responsive { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; }
        .report-card { background: #fff; border-radius: 16px; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .img-preview-mobile { width: 85px; height: 85px; border-radius: 12px; object-fit: cover; }
        .step.active { color: var(--emerald); font-weight: bold; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: #e2e8f0; margin: 0 auto 5px; }
        .step.active .step-dot { background: var(--emerald); }

        /* Responsivité */
        @media (max-width: 991px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; box-shadow: 5px 0 15px rgba(0,0,0,0.2); }
            main { margin-left: 0; width: 100%; padding: 15px; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <aside class="sidebar p-3 text-white" id="sidebar">
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <h5 class="fw-bold m-0">SENEGALSET</h5>
            <button class="btn btn-link text-white d-lg-none p-0" onclick="document.getElementById('sidebar').classList.remove('active')">
                <i class="bi bi-x-lg fs-4"></i>
            </button>
        </div>
            <a href="dashboard.php" class="nav-link text-white bg-opacity-25 rounded px-3 py-2"><i class="bi bi-speedometer2 me-2"></i> Tableau de bord</a>
            <a href="users.php" class="nav-link text-white bg-opacity-25 rounded px-3 py-2"><i class="bi bi-people me-2"></i> Utilisateurs</a>
            <a href="zones.php" class="nav-link text-white bg-opacity-25 rounded px-3 py-2"><i class="bi bi-map me-2"></i> Zones</a>
            <a href="categories.php" class="nav-link text-white bg-opacity-25 rounded px-3 py-2"><i class="bi bi-tags me-2"></i> Catégories</a>
            <a href="signalements.php" class="nav-link text-white bg-success bg-opacity-25 rounded px-3 py-2"><i class="bi bi-exclamation-triangle me-2"></i> Signalements</a>
            <a href="../auth/logout.php" class="nav-link text-white bg-opacity-25 rounded px-3 py-2 mt-auto"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a>
    </aside>
    
    <main>
        <div class="d-flex align-items-center mb-4">
            <button class="btn btn-success d-lg-none me-3" onclick="document.getElementById('sidebar').classList.add('active')">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h4 class="fw-bold m-0">Gestion des Signalements</h4>
        </div>

        <!-- Vue Desktop -->
        <div class="d-none d-lg-block table-responsive shadow-sm">
            <table class="table table-hover mb-0">
                <thead><tr><th>Photo</th><th>ID</th><th>Citoyen</th><th>Détails</th><th>Statut</th></tr></thead>
                <tbody>
                    <?php foreach ($reports as $rep): ?>
                    <tr>
                        <td><img src="../uploads/reports/<?= htmlspecialchars($rep['image'] ?? '') ?>" class="img-box-preview" style="width:50px;height:50px;border-radius:8px;object-fit:cover;"></td>
                        <td class="fw-bold text-primary">#<?= $rep['id'] ?></td>
                        <td><?= htmlspecialchars($rep['user_name'] ?? 'Anonyme') ?></td>
                        <td><?= htmlspecialchars($rep['category_name'] ?? 'Général') ?></td>
                        <td>
                            <form method="POST"><input type="hidden" name="action" value="update_report_status"><input type="hidden" name="report_id" value="<?= $rep['id'] ?>"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><option value="En attente" <?= $rep['status'] === 'En attente' ? 'selected' : '' ?>>En attente</option><option value="En cours" <?= $rep['status'] === 'En cours' ? 'selected' : '' ?>>En cours</option><option value="Résolu" <?= $rep['status'] === 'Résolu' ? 'selected' : '' ?>>Résolu</option></select></form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Vue Mobile -->
        <div class="d-block d-lg-none">
            <?php foreach ($reports as $rep): 
                $p = ($rep['status'] === 'En attente') ? 1 : (($rep['status'] === 'En cours') ? 3 : 4);
            ?>
            <div class="report-card">
                <div class="d-flex gap-3">
                    <img src="../uploads/reports/<?= htmlspecialchars($rep['image'] ?? '') ?>" class="img-preview-mobile">
                    <div>
                        <span class="text-success fw-bold">#<?= $rep['id'] ?></span>
                        <h6 class="fw-bold"><?= htmlspecialchars($rep['category_name'] ?? 'Incident') ?></h6>
                    </div>
                </div>
                <div class="stepper">
                    <div class="step <?= $p >= 1 ? 'active' : '' ?>"><div class="step-dot"></div>En attente</div>
                    <div class="step <?= $p >= 2 ? 'active' : '' ?>"><div class="step-dot"></div>Validé</div>
                    <div class="step <?= $p >= 3 ? 'active' : '' ?>"><div class="step-dot"></div>En cours</div>
                    <div class="step <?= $p >= 4 ? 'active' : '' ?>"><div class="step-dot"></div>Terminé</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>