<?php
session_start();
require_once "../config.php";

function registerUser() {
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);
    $username = trim($data["username"] ?? '');
    $password = trim($data["password"] ?? '');
    $confirm_password = trim($data["confirm_password"] ?? '');

    if (!$username || !$password || !$confirm_password) {
        echo json_encode(["error" => "Tous les champs sont requis."]);
        return;
    }

    if ($password !== $confirm_password) {
        echo json_encode(["error" => "Les mots de passe ne correspondent pas."]);
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(["error" => "Nom d'utilisateur déjà pris."]);
        return;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashed]);
    $user_id = $pdo->lastInsertId();

    // Ajouter les 10 cartes de base
    $starter_cards = [1,2,3,4,5,6,7,8,9,10];
    $stmt = $pdo->prepare("INSERT INTO inventory (user_id, card_id) VALUES (?, ?)");
    foreach ($starter_cards as $card_id) {
        $stmt->execute([$user_id, $card_id]);
    }

    echo json_encode(["success" => "Inscription réussie"]);
}

function loginUser() {
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);
    $username = trim($data["username"] ?? '');
    $password = trim($data["password"] ?? '');

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $username;
        echo json_encode(["success" => "Connexion réussie"]);
    } else {
        echo json_encode(["error" => "Identifiants invalides"]);
    }
}

function logoutUser() {
    session_destroy();
    echo json_encode(["success" => "Déconnexion réussie"]);
}
