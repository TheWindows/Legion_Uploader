<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $conn = db();
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hash);
    $stmt->fetch();

    if ($hash && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        header("Location: index.php");
        exit;
    } else {
        header("Location: login.php?error=invalid");
        exit;
    }
}
?>
