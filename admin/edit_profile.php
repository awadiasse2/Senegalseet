<?php
// client/edit_profile.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// 1. CHARGEMENT DES DONNÉES ACTUELLES
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../auth/logout.php');
        exit();
    }
} catch (PDOException $e) {
    $error_msg = "Erreur de chargement : " . $e->getMessage();
}

// 2. TRAITEMENT DU FORMULAIRE DE MISE À JOUR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $avatar_filename = $user['avatar']; // Par défaut, on garde l'ancien

    if (empty($name)) {
        $error_msg = "Le nom complet est obligatoire.";
    } else {
        try {
            // Gestion du téléversement de l'avatar
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['avatar']['tmp_name'];
                $file_name = $_FILES['avatar']['name'];
                $file_size = $_FILES['avatar']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error_msg = "Format d'image non valide (JPG, PNG ou WEBP uniquement).";
                } elseif ($file_size > 2 * 1024 * 1024) { // Limite à 2 Mo
                    $error_msg = "L'image ne doit pas dépasser 2 Mo.";
                } else {
                    // Créer le dossier s'il n'existe pas
                    $upload_dir = "../uploads/avatars/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    // Générer un nom unique pour éviter les conflits
                    $new_avatar_name = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
                    $dest_path = $upload_dir . $new_avatar_name;

                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        // Supprimer l'ancien avatar s'il existe et n'est pas vide
                        if (!empty($user['avatar']) && file_exists($upload_dir . $user['avatar'])) {
                            unlink($upload_dir . $user['avatar']);
                        }
                        $avatar_filename = $new_avatar_name;
                    } else {
                        $error_msg = "Erreur lors du déplacement du fichier.";
                    }
                }
            }

            // Si aucune erreur intermédiaire, on met à jour la base de données
            if (empty($error_msg)) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, avatar = ? WHERE id = ?");
                if ($stmt->execute([$name, $phone, $avatar_filename, $user_id])) {
                    $success_msg = "Profil mis à jour avec succès !";
                    
                    // Recharger les données fraîches
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_msg = "Impossible de sauvegarder les modifications.";
                }
            }

        } catch (PDOException $e) {
            $error_msg = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// URL de l'avatar actuel
$avatar_url = (!empty($user['avatar']) && file_exists("../uploads/avatars/" . $user['avatar'])) 
    ? "../uploads/avatars/" . htmlspecialchars($user['avatar']) 
    : "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENEGALSET - Modifier mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald: #00a651;
            --bg-body: #f8fafc;
            --text-dark: #0f172a;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #475569;
            font-size: 0.85rem;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.015);
            padding: 24px;
            margin-bottom: 20px;
        }
        .avatar-preview-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 15px auto;
        }
        .avatar-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .file-input-label {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--emerald);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .file-input-label:hover {
            transform: scale(1.1);
        }
        .form-control:focus {
            border-color: var(--emerald);
            box-shadow: 0 0 0 0.25rem rgba(0, 166, 81, 0.15);
        }
        .btn-submit {
            background-color: var(--emerald);
            color: white;
            border: none;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 20px;
        }
        .btn-submit:hover {
            background-color: #008f43;
            color: white;
        }
        .btn-cancel {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            font-weight: 500;
            border-radius: 10px;
            padding: 10px 20px;
        }
        .bottom-nav {
            background-color: #ffffff;
            border-top: 1px solid #f1f5f9;
        }
        .nav-link-custom {
            color: #94a3b8;
            font-size: 0.72rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .nav-link-custom.active {
            color: var(--emerald);
        }
        @media (max-width: 767.98px) {
            body { padding-bottom: 75px; }
        }
    </style>
</head>
<body>

<div class="container-md px-3 py-4" style="max-width: 600px;">
    
    <!-- RETOUR -->
    <div class="mb-3">
        <a href="profils.php" class="text-decoration-none text-muted fw-medium small">
            <i class="bi bi-arrow-left me-1"></i> Retour au profil
        </a>
    </div>

    <!-- EN-TÊTE -->
    <div class="mb-4 text-center">
        <a href="edit_profil.php">Modifier mon profil</a>
        <small class="text-muted">Mettez à jour vos informations publiques</small>
    </div>

    <!-- MESSAGES ALERTES -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger rounded-3 small" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?= $error_msg ?></div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success rounded-3 small" role="alert"><i class="bi bi-check-circle me-2"></i><?= $success_msg ?></div>
    <?php endif; ?>

    <!-- FORMULAIRE -->
    <div class="card-custom">
        <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
            
            <!-- SECTION AVATAR -->
            <div class="text-center mb-4">
                <div class="avatar-preview-container">
                    <img src="<?= $avatar_url ?>" id="avatarPreview" class="avatar-preview" alt="Aperçu avatar">
                    <label for="avatarInput" class="file-input-label">
                        <i class="bi bi-camera-fill" style="font-size: 13px;"></i>
                    </label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/png, image/jpeg, image/jpg, image/webp" class="d-none">
                </div>
                <small class="text-muted d-block" style="font-size: 11px;">Formats acceptés : JPG, PNG, WEBP (Max 2Mo)</small>
            </div>

            <!-- CHAMP : NOM -->
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold text-dark small">Nom complet</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 py-2 ps-1" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
            </div>

            <!-- CHAMP : EMAIL (BLOQUÉ) -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Adresse Email (Non modifiable)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control bg-light border-start-0 py-2 ps-1" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly disabled style="cursor: not-allowed;">
                </div>
                <small class="text-muted" style="font-size: 10px;">Veuillez contacter le support si vous devez changer d'email.</small>
            </div>

            <!-- CHAMP : TÉLÉPHONE -->
            <div class="mb-4">
                <label for="phone" class="form-label fw-semibold text-dark small">Numéro de téléphone</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 py-2 ps-1" id="phone" name="phone" placeholder="+221 7X XXX XX XX" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="d-flex gap-2">
                <a href="profil.php" class="btn btn-cancel w-50 flex-grow-1">Annuler</a>
                <button type="submit" class="btn btn-submit w-50 flex-grow-1">Sauvegarder</button>
            </div>

        </form>
    </div>

</div>

<!-- BARRE DE NAVIGATION COMMUNE MOBILE FIXED -->
<nav class="navbar bottom-nav fixed-bottom py-2 d-md-none">
    <div class="container-fluid d-flex justify-content-around">
        <a href="accueil.php" class="nav-link-custom"><i class="bi bi-house mb-1" style="font-size: 1.1rem;"></i>Accueil</a>
        <a href="signaler.php" class="nav-link-custom"><i class="bi bi-megaphone mb-1" style="font-size: 1.1rem;"></i>Signaler</a>
        <a href="carte.php" class="nav-link-custom"><i class="bi bi-map mb-1" style="font-size: 1.1rem;"></i>Carte</a>
        <a href="notifications.php" class="nav-link-custom"><i class="bi bi-bell mb-1" style="font-size: 1.1rem;"></i>Notifs</a>
        <a href="profil.php" class="nav-link-custom active"><i class="bi bi-person-fill mb-1" style="font-size: 1.1rem;"></i>Profil</a>
    </div>
</nav>

<!-- JavaScript pour prévisualiser l'avatar sélectionné avant l'envoi -->
<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('avatarPreview').src = event.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>