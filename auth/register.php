<?php
// auth/register.php - Inscription des Clients / Citoyens
session_start();
require_once '../config/config.php'; // Ajuste ce chemin si ton dossier config est placé différemment

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $role = trim($_POST['role'] ? 'Client');

    if (!empty($name) && !empty($email) && !empty($password) && !empty($password_confirm)) {
        
        // Vérification de la correspondance des mots de passe
        if ($password !== $password_confirm) {
            $error = "Les deux mots de passe ne correspondent pas.";
        } 
        // Vérification du format de l'email
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse email saisie n'est pas valide.";
        }
        elseif (!in_array($role, ['Client', 'Agent'], true)) {
            $error = "Le rôle sélectionné est invalide.";
        } 
        else {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cette adresse email est déjà enregistrée sur la plateforme.";
            } else {
                // Utilisation de PASSWORD_DEFAULT pour s'aligner parfaitement avec login.php
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion du rôle choisi par l'utilisateur
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                    $success = "Votre compte a été créé avec succès ! Redirection vers la page de connexion...";
                    // Étant dans le dossier auth/, login.php est dans le même répertoire
                    header("refresh:2;url=login.php");
                } else {
                    $error = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
                }
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SenegalSet — Créer un compte</title>
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts (Plus Jakarta Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
        }
        .register-card {
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
            max-width: 480px;
            width: 100%;
            background: #ffffff;
        }
        .btn-register {
            background-color: #008751;
            color: white;
            font-weight: 600;
            border: none;
            padding: 13px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background-color: #006e41;
            color: white;
            transform: translateY(-1px);
        }
        .form-control:focus {
            border-color: #008751;
            box-shadow: 0 0 0 0.25rem rgba(0, 135, 81, 0.15);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5 px-3">

    <div class="card register-card p-4 p-sm-5">
        
        <!-- Retour à l'accueil (on remonte d'un dossier) -->
        <div class="mb-4">
            <a href="../index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
        </div>

        <div class="text-center mb-4">
            <h2 class="fw-bold m-0" style="color: #07162c;"><span style="color: #008751;">Senegal</span>Set</h2>
            <p class="text-muted small mt-1">Rejoignez la communauté pour agir sur votre environnement</p>
        </div>

        <!-- Alertes de validation PHP -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2.5 text-center border-0 small mb-4" style="background-color: #fcdede; color: #c23b3b; border-radius: 10px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success py-2.5 text-center border-0 small mb-4" style="background-color: #ecfdf5; color: #065f46; border-radius: 10px;">
                <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <!-- Nom complet -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Nom et Prénom</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="name" class="form-control bg-light border-start-0 ps-0" placeholder="Ex: Ousmane Niang" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Adresse Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control bg-light border-start-0 ps-0" placeholder="exemple@domaine.sn" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
            </div>
            <!-- role agent ou Client -->
             <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Rôle</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-badge"></i></span>
                    <select name="role" class="form-select bg-light border-start-0 ps-0" required>
                        <option value="" disabled selected>Choisissez un rôle</option>
                        <option value="Client" <?= (isset($_POST['role']) && $_POST['role'] === 'Client') ? 'selected' : '' ?>>Client</option>
                        <option value="Agent" <?= (isset($_POST['role']) && $_POST['role'] === 'Agent') ? 'selected' : '' ?>>Agent Municipal</option>
                    </select>
                </div>
            </div>
            <!--region -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Région</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                    <select name="region" id="region" class="form-select bg-light border-start-0 ps-0" required>
                        <option value="" disabled selected>Choisissez une région</option>
                        <option value="Dakar" <?= (isset($_POST['region']) && $_POST['region'] === 'Dakar') ? 'selected' : '' ?>>Dakar</option>
                        <option value="Thiès" <?= (isset($_POST['region']) && $_POST['region'] === 'Thiès') ? 'selected' : '' ?>>Thiès</option>
                        <option value="Saint-Louis" <?= (isset($_POST['region']) && $_POST['region'] === 'Saint-Louis') ? 'selected' : '' ?>>Saint-Louis</option>
                        <option value="Ziguinchor" <?= (isset($_POST['region']) && $_POST['region'] === 'Ziguinchor') ? 'selected' : '' ?>>Ziguinchor</option>
                        <option value="Kaolack" <?= (isset($_POST['region']) && $_POST['region'] === 'Kaolack') ? 'selected' : '' ?>>Kaolack</option>
                        <option value="Tambacoundou" <?= (isset($_POST['region']) && $_POST['region'] === 'Tambacoundou') ? 'selected' : '' ?>>Tambacoundou</option>
                        <option value="Kolda" <?= (isset($_POST['region']) && $_POST['region'] === 'Kolda') ? 'selected' : '' ?>>Kolda</option>
                        <option value="Fatick" <?= (isset($_POST['region']) && $_POST['region'] === 'Fatick') ? 'selected' : '' ?>>Fatick</option>
                        <option value="Louga" <?= (isset($_POST['region']) && $_POST['region'] === 'Louga') ? 'selected' : '' ?>>Louga</option>
                        <option value="Matam" <?= (isset($_POST['region']) && $_POST['region'] === 'Matam') ? 'selected' : '' ?>>Matam</option>
                    </select>
                </div>
            </div>
            <!--communes -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Commune</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-geo-alt-fill"></i></span>
                    <select name="commune" id="commune" class="form-select bg-light border-start-0 ps-0" required disabled>
                        <option value="" selected disabled>Choisissez une commune</option>
                    </select>
                </div>
            </div>
               


            <!-- Mot de passe -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0 ps-0" placeholder="Minimum 6 caractères" minlength="6" required>
                </div>
            </div>

            <!-- Confirmation Mot de passe -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary small">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                    <input type="password" name="password_confirm" class="form-control bg-light border-start-0 ps-0" placeholder="Répétez le mot de passe" required>
                </div>
            </div>

            <!-- Bouton d'envoi -->
            <button type="submit" class="btn btn-register w-100 shadow-sm mb-3">Créer mon compte</button>

            <!-- Lien alternatif -->
            <div class="text-center mt-3">
                <p class="text-muted small mb-0">Vous possédez déjà un compte ? <a href="login.php" class="text-success fw-semibold text-decoration-none">Se connecter</a></p>
            </div>
        </form>
    </div>

    <script>
        const communesByRegion = {
            Dakar: ['Dakar-Plateau', 'Médina', 'Fann-Point E-Amitié', 'Grand Dakar', 'Parcelles Assainies', 'Ngor', 'Ouakam', 'Guédiawaye', 'Pikine Est', 'Keur Massar Nord'],
            'Thiès': ['Thiès Est', 'Thiès Nord', 'Thiès Ouest', 'Mbour', 'Joal-Fadiouth', 'Tivaouane'],
            'Saint-Louis': ['Saint-Louis', 'Dagana', 'Richard-Toll', 'Podor'],
            Ziguinchor: ['Ziguinchor', 'Bignona', 'Oussouye'],
            Kaolack: ['Kaolack', 'Nioro du Rip', 'Guinguinéo'],
            Tambacoundou: ['Tambacoundou', 'Bakel', 'Goudiry'],
            Kolda: ['Kolda', 'Vélingara', 'Médina Yoro Foulah'],
            Fatick: ['Fatick', 'Foundiougne', 'Gossas'],
            Louga: ['Louga', 'Kébémer', 'Linguère'],
            Matam: ['Matam', 'Ourossogui', 'Kanel']
        };

        document.addEventListener('DOMContentLoaded', function () {
            const regionSelect = document.getElementById('region');
            const communeSelect = document.getElementById('commune');
            const selectedRegion = <?= json_encode(isset($_POST['region']) ? $_POST['region'] : '') ?>;
            const selectedCommune = <?= json_encode(isset($_POST['commune']) ? $_POST['commune'] : '') ?>;

            function populateCommunes(region) {
                communeSelect.innerHTML = '<option value="" selected disabled>Choisissez une commune</option>';
                communeSelect.disabled = true;

                if (!region || !communesByRegion[region]) {
                    return;
                }

                communeSelect.disabled = false;
                communesByRegion[region].forEach(function (commune) {
                    const option = document.createElement('option');
                    option.value = commune;
                    option.textContent = commune;
                    if (commune === selectedCommune) {
                        option.selected = true;
                    }
                    communeSelect.appendChild(option);
                });
            }

            if (selectedRegion) {
                regionSelect.value = selectedRegion;
                populateCommunes(selectedRegion);
            }

            regionSelect.addEventListener('change', function () {
                populateCommunes(this.value);
            });
        });
    </script>
</body>
</html>