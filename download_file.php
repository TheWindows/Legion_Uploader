<?php
require_once "db.php";

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header("Location: index.php");
    exit;
}

$conn = db();

$stmt = $conn->prepare("SELECT filename_original, filename_saved FROM files WHERE code = ? AND expires_at > NOW()");
$stmt->bind_param("s", $code);
$stmt->execute();
$stmt->bind_result($original, $saved);
$stmt->fetch();
$stmt->close();

if (!$saved) {
    header("Location: index.php");
    exit;
}

$path = __DIR__ . "/uploads/" . $saved;
if (!file_exists($path)) {
    header("Location: index.php");
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($original) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;
?>