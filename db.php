<?php
require_once "config.php";

function db() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

function init_db_tables() {
    $conn = db();
    
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
        $conn->query("ALTER TABLE users ADD COLUMN max_upload_size BIGINT DEFAULT 1073741824");
    }
    
    $conn->query("UPDATE users SET is_admin = TRUE WHERE id = 1");
}

function cleanup_expired_files() {
    $conn = db();
    $expired_files = $conn->query("SELECT id, filename_saved, size, user_id FROM files WHERE expires_at < NOW()");
    
    $deleted_count = 0;
    while($row = $expired_files->fetch_assoc()){
        $file_path = __DIR__ . "/uploads/" . $row['filename_saved'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $conn->query("DELETE FROM files WHERE id=".$row['id']);
        $conn->query("UPDATE users SET used_space = used_space - ".$row['size']." WHERE id=".$row['user_id']);
        $deleted_count++;
    }
    
    return $deleted_count;
}

init_db_tables();
cleanup_expired_files();
?>