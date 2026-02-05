<?php
require_once "db.php";

$conn = db();

$stmt = $conn->query("SELECT id, filename_saved, size, user_id FROM files WHERE expires_at < NOW()");
while($row = $stmt->fetch_assoc()){
    @unlink(__DIR__ . "/uploads/" . $row['filename_saved']);
    $conn->query("DELETE FROM files WHERE id=".$row['id']);
    $conn->query("UPDATE users SET used_space = used_space - ".$row['size']." WHERE id=".$row['user_id']);
}
echo "Expired files deleted.";
?>