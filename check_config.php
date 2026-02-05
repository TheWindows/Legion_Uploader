<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Configuration Check</h2>";

$uploadDir = __DIR__ . "/uploads/";
echo "Upload Directory: $uploadDir<br>";
echo "Directory exists: " . (is_dir($uploadDir) ? "Yes" : "No") . "<br>";
echo "Directory writable: " . (is_writable($uploadDir) ? "Yes" : "No") . "<br>";

echo "<h3>PHP Settings</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h3>Database Check</h3>";
require_once "db.php";
$conn = db();
if ($conn->connect_error) {
    echo "Database error: " . $conn->connect_error . "<br>";
} else {
    echo "Database connected successfully<br>";
    
    $tables = ['users', 'files'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "Table '$table' exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "<br>";
    }
}

echo "<h3>Session Check</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";

echo "<h3>Test File Upload</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['testfile'])) {
    $file = $_FILES['testfile'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . " bytes<br>";
    echo "File type: " . $file['type'] . "<br>";
    echo "File error: " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmp = $file['tmp_name'];
        $dest = $uploadDir . "test_" . basename($file['name']);
        if (move_uploaded_file($tmp, $dest)) {
            echo "File uploaded successfully to: $dest<br>";
            unlink($dest);
        } else {
            echo "Failed to move uploaded file<br>";
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="testfile">
    <button type="submit">Test Upload</button>
</form>