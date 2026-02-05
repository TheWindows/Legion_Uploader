<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['code'])) {
    header("Location: dashboard.php");
    exit;
}

$file_id = $_GET['id'];
$file_code = $_GET['code'];
$user_id = $_SESSION['user_id'];

$conn = db();

$stmt = $conn->prepare("SELECT filename_saved, size, user_id FROM files WHERE id = ? AND code = ?");
$stmt->bind_param("is", $file_id, $file_code);
$stmt->execute();
$stmt->bind_result($filename_saved, $size, $file_owner_id);
$stmt->fetch();
$stmt->close();

if (!$filename_saved || $file_owner_id != $user_id) {
    header("Location: dashboard.php");
    exit;
}

$conn->query("DELETE FROM files WHERE id = $file_id AND code = '$file_code'");
@unlink(__DIR__ . "/uploads/" . $filename_saved);

$conn->query("UPDATE users SET used_space = used_space - $size WHERE id = $user_id");

header("Location: dashboard.php?deleted=1");
exit;
?>