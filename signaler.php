<?php
// client/signaler.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/config.php';

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// ===================================================
// RÉCUPÉRATION DU PROFIL ET DE L'AVATAR RÉEL DU CLIENT
// ===================================================
try {
    $stmt_user = $pdo->prepare("SELECT name, role, avatar FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_user = null;
}

$user_name = !empty($current_user['name']) ? $current_user['name'] : 'Citoyen';
$user_role = !empty($current_user['role']) ? $current_user['role'] : 'Client';

// Gestion du chemin d'accès de l'avatar (fallback si vide)
if (!empty($current_user['avatar'])) {
    if (strpos($current_user['avatar'], 'http') === 0) {
        $user_avatar = $current_user['avatar'];
    } else {
        $user_avatar = "../uploads/avatars/" . $current_user['avatar'];
    }
} else {
    $user_avatar = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";
}
// ===================================================

// Traitement de l'envoi du signalement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    
    // Vérification et affectation de NULL si aucune catégorie valide n'est reçue
    $category_id = !empty($_POST['category']) ? intval($_POST['category']) : null;
    
    $latitude = !empty($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? trim($_POST['longitude']) : null;
    $image_filename = null;

    // Validation des champs obligatoires (Description et Catégorie)
    if (empty($description) || is_null($category_id)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires (Description, Catégorie).";
    } else {
        try {
            // Gestion du téléversement de la photo
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($file_ext, $allowed_extensions)) {
                    $error_msg = "Seules les images JPG, JPEG, PNG et WEBP sont autorisées.";
                } else {
                    $image_filename = uniqid('report_', true) . '.' . $file_ext;
                    $upload_dir = '../uploads/reports/';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    if (!move_uploaded_file($file_tmp, $upload_dir . $image_filename)) {
                        $error_msg = "Échec du téléversement de l'image.";
                    }
                }
            }

            // Si aucune erreur de téléversement, on procède à l'insertion
            if (empty($error_msg)) {
                
                // Évite l'erreur de contrainte d'intégrité en récupérant une zone existante
                $checkZone = $pdo->query("SELECT id FROM zones LIMIT 1")->fetch();
                $zone_id = $checkZone ? $checkZone['id'] : null; 

                // Requête SQL alignée sans la colonne 'title' obsolète
                $stmt = $pdo->prepare("INSERT INTO reports (user_id, category_id, zone_id, description, latitude, longitude, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'En attente', NOW())");
                $stmt->execute([
                    $user_id,
                    $category_id,
                    $zone_id,
                    $description,
                    $latitude,
                    $longitude,
                    $image_filename
                ]);

                $success_msg = "Votre signalement a été enregistré avec succès ! Nos équipes vont l'étudier.";
            }

        } catch (PDOException $e) {
            $error_msg = "Erreur de base de données : " . $e->getMessage();
        }
    }
}

// Récupération des catégories pour alimenter dynamiquement le sélecteur HTML
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signaler une anomalie — SenegalSet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-color: #008751; 
            --secondary-color: #ffb703; 
            --background-color: #f8f9fa; 
            --dark-color: #1a1a1a; 
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--background-color); 
            color: var(--dark-color); 
            padding-bottom: 90px; 
        }
        .card-custom { 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); 
            background: #ffffff; 
        }
        .btn-success-custom { 
            background-color: var(--primary-color); 
            border: none; 
            border-radius: 10px; 
            font-weight: 500; 
            padding: 12px 20px; 
            transition: all 0.3s ease; 
        }
        .btn-success-custom:hover { 
            background-color: #006e41; 
            transform: translateY(-1px); 
        }
        .form-control, .form-select { 
            border-radius: 10px; 
            border: 1px solid #e2e8f0; 
            padding: 10px 14px; 
            font-size: 14px; 
        }
        .form-control:focus, .form-select:focus { 
            border-color: var(--primary-color); 
            box-shadow: 0 0 0 3px rgba(0, 135, 81, 0.1); 
        }
        .upload-box { 
            border: 2px dashed #cbd5e1; 
            border-radius: 12px; 
            padding: 24px; 
            text-align: center; 
            cursor: pointer; 
            background: #fdfdfd; 
            transition: all 0.2s ease; 
        }
        .upload-box:hover { 
            border-color: var(--primary-color); 
            background: #f4faf7; 
        }
        .header-avatar { object-fit: cover; border: 1px solid #e2e8f0; }
        
        /* Bottom Navigation responsive et propre */
        .bottom-nav { 
            background: #ffffff; 
            border-top: 1px solid #edeef0; 
            box-shadow: 0 -4px 10px rgba(0,0,0,0.03); 
        }
        .nav-link-custom { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            font-size: 11px; 
            color: #7a7a7a; 
            text-decoration: none; 
            font-weight: 500; 
            padding: 4px 0;
        }
        .nav-link-custom i {
            font-size: 1.25rem;
            margin-bottom: 2px;
        }
        .nav-link-custom.active { 
            color: var(--primary-color); 
            font-weight: 600; 
        }
    </style>
</head>
<body>

<div class="container py-4 px-3" style="max-width: 540px;">
    <!-- Retour à l'accueil -->
    <div class="mb-3">
        <a href="espace_client.php" class="text-decoration-none text-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left-circle-fill fs-4"></i> <span class="fw-medium text-sm">Retour à l'accueil</span>
        </a>
    </div>
    
    <!-- Header d'action -->
    <div class="d-flex align-items-center justify-content-between mb-4 gap-2">
        <h4 class="fw-bold m-0 text-dark">Nouveau signalement</h4>
        <div class="d-flex align-items-center gap-2 bg-white p-1 pe-2 border rounded-4 shadow-sm flex-shrink-0">
            <img src="<?= htmlspecialchars($user_avatar) ?>" class="rounded-circle header-avatar" width="32" height="32" alt="Avatar">
            <span class="fw-bold text-dark d-none d-sm-inline" style="font-size: 11px; max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($user_name) ?></span>
        </div>
    </div>

    <!-- Alertes responsives -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm d-flex align-items-center gap-2" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i> <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success border-0 rounded-3 shadow-sm d-flex align-items-center gap-2" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i> <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <div class="card card-custom p-3 p-sm-4">
        <form action="signaler.php" method="POST" enctype="multipart/form-data">
            
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark" style="font-size: 13px;">Type d'anomalie <span class="text-danger">*</span></label>
                <select name="category" class="form-select" required>
                    <option value="" disabled selected>Choisir une catégorie...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= intval($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-dark" style="font-size: 13px;">Description <span class="text-danger">*</span></label>
                <textarea name="description" rows="4" class="form-control" placeholder="Décrivez le problème avec précision (emplacement exact, danger potentiel...)" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-dark" style="font-size: 13px;">Ajouter une photo</label>
                <div class="upload-box" onclick="document.getElementById('imageInput').click()">
                    <i class="bi bi-camera text-muted fs-2 mb-2 d-block"></i>
                    <span class="text-sm text-secondary d-block fw-medium mb-1" id="uploadText" style="font-size: 13px;">Prendre une photo ou choisir un fichier</span>
                    <small class="text-muted d-block" style="font-size: 11px;">Formats acceptés : JPG, PNG, WEBP</small>
                    <div class="w-100 text-center mt-3">
                        <img id="previewImg" src="#" alt="Aperçu" class="rounded img-fluid" style="max-height: 140px; display: none; object-fit: cover;">
                    </div>
                </div>
                <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;">
            </div>

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="mb-4">
                <button type="button" class="btn btn-light border w-100 rounded-3 py-2 fw-medium text-secondary" style="font-size: 13px;" onclick="getLocation()">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>Utiliser ma position GPS actuelle
                </button>
            </div>

            <button type="submit" class="btn btn-success-custom w-100 text-white shadow-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-send-fill"></i> <span>Envoyer le signalement</span>
            </button>

        </form>
    </div>
</div>

<!-- Bottom Navigation Menu pour écrans Mobiles uniquement -->
<nav class="navbar bottom-nav fixed-bottom py-2 d-md-none">
    <div class="container-fluid d-flex justify-content-around px-0">
        <a href="espace_client.php" class="nav-link-custom"><i class="bi bi-house"></i>Accueil</a>
        <a href="signaler.php" class="nav-link-custom active"><i class="bi bi-megaphone-fill"></i>Signaler</a>
        <a href="carte.php" class="nav-link-custom"><i class="bi bi-map"></i>Carte</a>
        <a href="interventions.php" class="nav-link-custom"><i class="bi bi-tools"></i>Travaux</a>
    </div>
</nav>

<script>
// Gestion de la géolocalisation GPS
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
            alert("Position GPS détectée avec succès !");
        }, function(error) {
            alert("Impossible de récupérer la position GPS. Assurez-vous d'avoir activé la géolocalisation sur votre appareil.");
        });
    } else {
        alert("La géolocalisation n'est pas supportée par votre navigateur.");
    }
}

// Rendu immédiat et asynchrone de la photo avant soumission
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const preview = document.getElementById('previewImg');
            preview.src = event.target.result;
            preview.style.display = 'inline-block';
            document.getElementById('uploadText').innerText = "Changer la photo";
        }
        reader.readAsDataURL(file);
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>