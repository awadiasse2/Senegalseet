<?php
// admin/equipes_terrain.php
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }
$user_role = trim(strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? ''));
if ($user_role !== 'admin') { header('Location: ../client/espace_client.php'); exit(); }

require_once '../config/config.php';

$admin_name = $_SESSION['user_name'] ?? 'Admin Sénégalset';
$admin_avatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80';

$error_msg = ""; $success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'Disponible');
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO teams (name, status, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$name, $status]);
            $success_msg = "La brigade '$name' a été ajoutée.";
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $stmt = $pdo->prepare("UPDATE teams SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['team_id']]);
        $success_msg = "Statut mis à jour.";
    }
}

$teams = $pdo->query("SELECT id, name, status, created_at FROM teams ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Brigades & Équipes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --bg-sidebar: #06152d; --bg-body: #f3f6f9; --emerald: #00a651; }
        body { font-family: sans-serif; background-color: var(--bg-body); }
        .sidebar { width: 260px; height: 100vh; background-color: var(--bg-sidebar); position: fixed; top: 0; left: 0; z-index: 1000; transition: 0.3s; }
        main { margin-left: 260px; padding: 24px; transition: 0.3s; }
        @media (max-width: 991px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            main { margin-left: 0; width: 100% !important; padding-top: 70px; }
            .menu-toggle { display: block !important; }
        }
        .menu-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1100; }
    </style>
</head>
<body>
<button class="btn btn-success menu-toggle" id="toggleBtn"><i class="bi bi-list"></i></button>

<div class="d-flex">
    <aside class="sidebar p-3" id="sidebar">
        <h6 class="text-white mb-4">SENEGALSET</h6>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="Dashboard.php" class="nav-link text-white"><i class="bi bi-grid"></i> Dashboard</a></li>
            <li class="nav-item"><a href="signalements.php" class="nav-link text-white"><i class="bi bi-exclamation-triangle"></i> Signalements</a></li>
            <li class="nav-item"><a href="equipes_terrain.php" class="nav-link text-white bg-success"><i class="bi bi-shield-shaded"></i> Équipes</a></li>
            <li class="nav-item"><a href="interventions.php" class="nav-link text-white"><i class="bi bi-activity"></i> Interventions</a></li>
        </ul>
    </aside>

    <main class="flex-grow-1">
        <div class="d-flex justify-content-between mb-4">
            <h4>Gestion des Brigades</h4>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTeamModal">+ Nouvelle Brigade</button>
        </div>

        <?php if($success_msg): ?><div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div><?php endif; ?>

        <div class="row g-3">
            <?php foreach($teams as $team): ?>
            <div class="col-md-4">
                <div class="card p-3 shadow-sm border-0">
                    <h6><?= htmlspecialchars($team['name'] ?? '') ?></h6>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id'] ?? '') ?>">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="Disponible" <?= ($team['status'] ?? '') === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="En intervention" <?= ($team['status'] ?? '') === 'En intervention' ? 'selected' : '' ?>>En intervention</option>
                            <option value="Inactive" <?= ($team['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Modal -->
<div class="modal fade" id="addTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add">
            <div class="modal-header"><h5>Nouvelle Brigade</h5></div>
            <div class="modal-body">
                <input type="text" name="name" class="form-control mb-2" placeholder="Nom de l'équipe" required>
                <select name="status" class="form-select"><option value="Disponible">Disponible</option></select>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Enregistrer</button></div>
        </form>
    </div>
</div>

<script>
    document.getElementById('toggleBtn').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('active'); });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>