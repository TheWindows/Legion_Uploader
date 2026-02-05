<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = db();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($is_admin);
$stmt->fetch();
$stmt->close();

if (!$is_admin) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'cleanup_expired') {
        $stmt = $conn->query("SELECT id, filename_saved, size, user_id FROM files WHERE expires_at < NOW()");
        while($row = $stmt->fetch_assoc()){
            $file_path = __DIR__ . "/uploads/" . $row['filename_saved'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $conn->query("DELETE FROM files WHERE id=".$row['id']);
            $conn->query("UPDATE users SET used_space = used_space - ".$row['size']." WHERE id=".$row['user_id']);
        }
        
        header("Location: admin.php?success=cleanup_completed");
        exit;
        
    } elseif ($action === 'cleanup_orphaned') {
        $upload_dir = __DIR__ . "/uploads/";
        $files_in_db = [];
        
        $result = $conn->query("SELECT filename_saved FROM files");
        while($row = $result->fetch_assoc()){
            $files_in_db[] = $row['filename_saved'];
        }
        
        if (is_dir($upload_dir)) {
            $files = scandir($upload_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && !in_array($file, $files_in_db)) {
                    $file_path = $upload_dir . $file;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
        }
        
        header("Location: admin.php?success=orphaned_cleaned");
        exit;
    }
}

header("Location: admin.php");
exit;
?>