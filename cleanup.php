<?php
require_once "db.php";

$conn = db();

$result = $conn->query("SELECT id, filename_saved, size, user_id FROM files WHERE expires_at < NOW()");

$deleted_count = 0;
$errors = [];

while($row = $result->fetch_assoc()){
    $file_path = __DIR__ . "/uploads/" . $row['filename_saved'];
    
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            $errors[] = "Failed to delete file: " . $row['filename_saved'];
        }
    }
    
    $conn->query("DELETE FROM files WHERE id=" . $row['id']);
    $conn->query("UPDATE users SET used_space = used_space - " . $row['size'] . " WHERE id=" . $row['user_id']);
    $deleted_count++;
}

echo json_encode([
    'status' => 'success',
    'deleted_count' => $deleted_count,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>