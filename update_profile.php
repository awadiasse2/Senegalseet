<?php
// client/update_profile.php
session_start();
require_once '../config/config.php';

// Vérification de la session utilisateur
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: ../auth/login.php');
    exit();
}

// Sécurité : On s'assure que la requête vient bien d'un formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Nettoyage et récupération des données reçues
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Initialisation d'un tableau pour d'éventuelles erreurs de validation
    $_SESSION['errors'] = [];
    $_SESSION['success'] = null;

    // 1. Validation des champs obligatoires
    if (empty($name)) {
        $_SESSION['errors'][] = "Le nom complet ne peut pas être vide.";
    }

    // 2. Gestion et validation du changement de mot de passe
    $password_update_query = "";
    $query_params = [
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'id' => $user_id
    ];

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $_SESSION['errors'][] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['errors'][] = "Les deux mots de passe ne correspondent pas.";
        } else {
            // Hachage sécurisé du mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_update_query = ", password = :password";
            $query_params['password'] = $hashed_password;
        }
    }

    // 3. S'il y a des erreurs, on redirige directement vers la page profil
    if (!empty($_SESSION['errors'])) {
        header('Location: profil.php');
        exit();
    }

    try {
        // Préparation de la requête SQL dynamique
        $sql = "UPDATE users SET 
                    name = :name, 
                    phone = :phone, 
                    address = :address 
                    {$password_update_query} 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($query_params);

        if ($result) {
            // Mettre à jour la variable de session pour l'affichage dynamique immédiat
            $_SESSION['user_name'] = $name;
            $_SESSION['success'] = "Votre profil a été mis à jour avec succès.";
        } else {
            $_SESSION['errors'][] = "Une erreur est survenue lors de la mise à jour.";
        }

    } catch (PDOException $e) {
        $_SESSION['errors'][] = "Erreur de base de données : " . $e->getMessage();
    }

    // Redirection vers la page de profil pour afficher le résultat
    header('Location: profil.php');
    exit();
} else {
    // Si quelqu'un tente d'accéder au fichier directement par URL
    header('Location: profil.php');
    exit();
}