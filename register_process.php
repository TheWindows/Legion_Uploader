<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $conn = db();
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        header("Location: register.php?error=duplicate");
        exit;
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hash);

    if ($stmt->execute()) {
        header("Location: login.php?success=registered");
        exit;
    } else {
        header("Location: register.php?error=failed");
        exit;
    }
}
?>