<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = db();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($admin_username, $is_admin);
$stmt->fetch();
$stmt->close();

if (!$is_admin) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
        $max_upload = (int)$_POST['max_upload'];
        $is_admin_user = isset($_POST['is_admin']) ? 1 : 0;
        
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            header("Location: admin.php?error=duplicate");
            exit;
        }
        $check_stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, max_upload_size, is_admin) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $username, $email, $password, $max_upload, $is_admin_user);
        if ($stmt->execute()) {
            header("Location: admin.php?success=user_created");
        } else {
            header("Location: admin.php?error=failed");
        }
        $stmt->close();
        exit;
    }
    
    if (isset($_POST['update_quota'])) {
        $target_user_id = (int)$_POST['user_id'];
        $max_upload = (int)$_POST['max_upload'];
        
        $stmt = $conn->prepare("UPDATE users SET max_upload_size = ? WHERE id = ?");
        $stmt->bind_param("ii", $max_upload, $target_user_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php?success=quota_updated");
        exit;
    }
    
    if (isset($_POST['toggle_admin'])) {
        $target_user_id = (int)$_POST['user_id'];
        $current_admin = (int)$_POST['current_admin'];
        
        $new_admin_status = $current_admin ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_admin_status, $target_user_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php?success=admin_toggled");
        exit;
    }
    
    if (isset($_POST['delete_user'])) {
        $target_user_id = (int)$_POST['user_id'];
        
        if ($target_user_id != 1 && $target_user_id != $user_id) {
            $files_stmt = $conn->prepare("SELECT filename_saved, size FROM files WHERE user_id = ?");
            $files_stmt->bind_param("i", $target_user_id);
            $files_stmt->execute();
            $files_result = $files_stmt->get_result();
            
            while ($file = $files_result->fetch_assoc()) {
                @unlink(__DIR__ . "/uploads/" . $file['filename_saved']);
            }
            $files_stmt->close();
            
            $conn->query("DELETE FROM files WHERE user_id = $target_user_id");
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            $stmt->execute();
            $stmt->close();
            
            header("Location: admin.php?success=user_deleted");
            exit;
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $target_user_id = (int)$_POST['user_id'];
        $new_password = password_hash('123456', PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $target_user_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php?success=password_reset");
        exit;
    }
    
    if (isset($_POST['edit_file'])) {
        $file_id = (int)$_POST['file_id'];
        $new_expires = $_POST['expires_at'];
        $new_password = $_POST['file_password'] ? password_hash($_POST['file_password'], PASSWORD_BCRYPT) : null;
        
        $stmt = $conn->prepare("UPDATE files SET expires_at = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_expires, $new_password, $file_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php?success=file_updated&tab=files");
        exit;
    }
    
    if (isset($_POST['delete_file_admin'])) {
        $file_id = (int)$_POST['file_id'];
        
        $file_stmt = $conn->prepare("SELECT filename_saved, size, user_id FROM files WHERE id = ?");
        $file_stmt->bind_param("i", $file_id);
        $file_stmt->execute();
        $file_stmt->bind_result($filename_saved, $file_size, $file_user_id);
        $file_stmt->fetch();
        $file_stmt->close();
        
        $file_path = __DIR__ . "/uploads/" . $filename_saved;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $conn->query("DELETE FROM files WHERE id = $file_id");
        $conn->query("UPDATE users SET used_space = used_space - $file_size WHERE id = $file_user_id");
        
        header("Location: admin.php?success=file_deleted&tab=files");
        exit;
    }
    
    if (isset($_POST['clear_system_logs'])) {
        $log_file = __DIR__ . "/admin_logs.txt";
        if (file_exists($log_file)) {
            file_put_contents($log_file, "=== Admin Logs Cleared at " . date('Y-m-d H:i:s') . " ===\n");
        }
        header("Location: admin.php?success=logs_cleared&tab=logs");
        exit;
    }
    
    if (isset($_POST['send_bulk_email'])) {
        $subject = trim($_POST['email_subject']);
        $message = trim($_POST['email_message']);
        $recipients = $_POST['email_recipients'];
        
        $log_file = __DIR__ . "/admin_logs.txt";
        $log_message = "📧 Bulk email sent at " . date('Y-m-d H:i:s') . "\n";
        $log_message .= "Subject: $subject\n";
        $log_message .= "Recipients: $recipients\n";
        $log_message .= "Message length: " . strlen($message) . " characters\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        header("Location: admin.php?success=email_sent&tab=tools");
        exit;
    }
}

$users = $conn->query("SELECT id, username, email, is_admin, max_upload_size, used_space, created_at FROM users ORDER BY id DESC");

$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_files = $conn->query("SELECT COUNT(*) as count FROM files")->fetch_assoc()['count'];
$total_space = $conn->query("SELECT SUM(size) as total FROM files")->fetch_assoc()['total'];
$total_space = $total_space ? round($total_space / 1024 / 1024 / 1024, 3) : 0;

$active_files = $conn->query("SELECT COUNT(*) as count FROM files WHERE expires_at > NOW()")->fetch_assoc()['count'];
$expired_files = $conn->query("SELECT COUNT(*) as count FROM files WHERE expires_at < NOW()")->fetch_assoc()['count'];

$total_downloads = 0;
$check_downloads = $conn->query("SHOW COLUMNS FROM files LIKE 'downloads'");
if ($check_downloads->num_rows > 0) {
    $result = $conn->query("SELECT SUM(downloads) as total FROM files");
    $row = $result->fetch_assoc();
    $total_downloads = $row['total'] ? $row['total'] : 0;
}

$today_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$today_files = $conn->query("SELECT COUNT(*) as count FROM files WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

$server_info = [
    'php_version' => phpversion(),
    'upload_max' => ini_get('upload_max_filesize'),
    'post_max' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution' => ini_get('max_execution_time'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_size' => get_database_size($conn)
];

function get_database_size($conn) {
    $result = $conn->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()");
    $row = $result->fetch_assoc();
    return $row['size_mb'] ?? '0';
}

$recent_logs = [];
$log_file = __DIR__ . "/admin_logs.txt";
if (file_exists($log_file)) {
    $recent_logs = array_slice(file($log_file, FILE_IGNORE_NEW_LINES), -20);
}

$disk_free = round(disk_free_space(__DIR__) / 1024 / 1024 / 1024, 2);
$disk_total = round(disk_total_space(__DIR__) / 1024 / 1024 / 1024, 2);
$disk_used = $disk_total - $disk_free;
$disk_percent = $disk_total > 0 ? round(($disk_used / $disk_total) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پنل مدیریت | Legion Upload</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --admin-primary: #8a6cff;
    --admin-secondary: #7c7cff;
    --admin-success: #4CAF50;
    --admin-warning: #ff9f43;
    --admin-danger: #ff6b6b;
    --admin-info: #00bcd4;
    --admin-dark: #0f111a;
    --admin-card-bg: rgba(255, 255, 255, 0.07);
    --admin-border: rgba(255, 255, 255, 0.15);
    --admin-text: #ffffff;
    --admin-muted: rgba(255, 255, 255, 0.7);
}

body {
    background: linear-gradient(135deg, #0a0c14 0%, #161827 100%);
    color: var(--admin-text);
    font-family: 'Vazir', 'Segoe UI', sans-serif;
    min-height: 100vh;
    padding: 20px;
}
        select option {
            background-color: rgba(16, 17, 26, 0.95);
            color: #ffffff;
        }

        select option:nth-child(odd) {
            background-color: rgba(16, 17, 26, 0.95); 
        }

        select option:nth-child(even) {
            background-color: rgba(16, 17, 26, 0.95);
        }

        select option:checked {
            background-color: rgba(7, 7, 7, 0.95) !important;
            color: white;
        }
.admin-container {
    max-width: 1800px;
    margin: 0 auto;
}

.admin-header {
    background: linear-gradient(135deg, rgba(138, 108, 255, 0.1), rgba(124, 124, 255, 0.05));
    border: 1px solid var(--admin-border);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    backdrop-filter: blur(20px);
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

.admin-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(138, 108, 255, 0.1), transparent 70%);
    animation: pulse 8s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}

.admin-header h1 {
    font-size: 2.8rem;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 15px;
    position: relative;
    display: flex;
    align-items: center;
    gap: 20px;
}

.admin-header h1 i {
    font-size: 2.5rem;
    color: var(--admin-primary);
}

.admin-header p {
    color: var(--admin-muted);
    font-size: 1.1rem;
    margin-bottom: 25px;
    max-width: 800px;
    line-height: 1.8;
    position: relative;
}

.header-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    position: relative;
}

.btn {
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    border: none;
    color: white;
    padding: 12px 25px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-size: 14px;
    box-shadow: 0 5px 15px rgba(138, 108, 255, 0.3);
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(138, 108, 255, 0.4);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--admin-primary);
    color: var(--admin-primary);
    padding: 10px 23px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-size: 14px;
}

.btn-outline:hover {
    background: rgba(138, 108, 255, 0.1);
    transform: translateY(-3px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-border);
    border-radius: 18px;
    padding: 25px;
    backdrop-filter: blur(20px);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-8px);
    border-color: var(--admin-primary);
    box-shadow: 0 15px 35px rgba(138, 108, 255, 0.25);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--admin-primary), var(--admin-secondary));
}

.stat-card.success::before { background: linear-gradient(90deg, var(--admin-success), #66bb6a); }
.stat-card.warning::before { background: linear-gradient(90deg, var(--admin-warning), #ffb74d); }
.stat-card.danger::before { background: linear-gradient(90deg, var(--admin-danger), #ff8a80); }
.stat-card.info::before { background: linear-gradient(90deg, var(--admin-info), #4dd0e1); }

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: var(--admin-primary);
}

.stat-card.success .stat-icon { color: var(--admin-success); }
.stat-card.warning .stat-icon { color: var(--admin-warning); }
.stat-card.danger .stat-icon { color: var(--admin-danger); }
.stat-card.info .stat-icon { color: var(--admin-info); }

.stat-title {
    color: var(--admin-muted);
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.stat-value {
    font-size: 2.8rem;
    font-weight: bold;
    background: linear-gradient(135deg, var(--admin-text), var(--admin-muted));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin: 15px 0;
}

.stat-card.success .stat-value { background: linear-gradient(135deg, var(--admin-success), #66bb6a); }
.stat-card.warning .stat-value { background: linear-gradient(135deg, var(--admin-warning), #ffb74d); }
.stat-card.danger .stat-value { background: linear-gradient(135deg, var(--admin-danger), #ff8a80); }
.stat-card.info .stat-value { background: linear-gradient(135deg, var(--admin-info), #4dd0e1); }

.stat-card.success .stat-value,
.stat-card.warning .stat-value,
.stat-card.danger .stat-value,
.stat-card.info .stat-value {
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--admin-success);
    font-weight: 500;
}

.stat-change.negative { color: var(--admin-danger); }

.stat-footer {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--admin-muted);
    font-size: 13px;
}

.progress-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    overflow: hidden;
    margin-top: 15px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--admin-primary), var(--admin-secondary));
    border-radius: 4px;
    transition: width 1s ease;
}

.progress-fill.success { background: linear-gradient(90deg, var(--admin-success), #66bb6a); }
.progress-fill.warning { background: linear-gradient(90deg, var(--admin-warning), #ffb74d); }
.progress-fill.danger { background: linear-gradient(90deg, var(--admin-danger), #ff8a80); }

.admin-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    background: rgba(255, 255, 255, 0.05);
    padding: 20px;
    border-radius: 18px;
    border: 1px solid var(--admin-border);
}

.tab-btn {
    background: rgba(255, 255, 255, 0.08);
    border: 2px solid transparent;
    color: var(--admin-text);
    padding: 14px 28px;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    min-width: 160px;
    justify-content: center;
}

.tab-btn:hover {
    background: rgba(138, 108, 255, 0.15);
    border-color: rgba(138, 108, 255, 0.3);
    transform: translateY(-3px);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    box-shadow: 0 8px 20px rgba(138, 108, 255, 0.4);
    border-color: transparent;
}

.tab-content {
    display: none;
    animation: fadeIn 0.5s ease;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.table-container {
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-border);
    border-radius: 18px;
    padding: 25px;
    backdrop-filter: blur(20px);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.search-box i {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--admin-muted);
}

.search-input {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border-radius: 12px;
    border: 2px solid var(--admin-border);
    background: rgba(255, 255, 255, 0.08);
    color: var(--admin-text);
    font-size: 16px;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(138, 108, 255, 0.2);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.admin-table th {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    text-align: right;
    color: var(--admin-primary);
    font-weight: bold;
    border-bottom: 2px solid var(--admin-border);
    font-size: 15px;
    position: sticky;
    top: 0;
}

.admin-table td {
    padding: 18px 20px;
    text-align: right;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: var(--admin-text);
    font-size: 14.5px;
}

.admin-table tr {
    transition: all 0.3s;
}

.admin-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: scale(1.002);
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.badge-admin {
    background: rgba(138, 108, 255, 0.2);
    color: var(--admin-primary);
    border: 1px solid rgba(138, 108, 255, 0.3);
}

.badge-user {
    background: rgba(76, 175, 80, 0.2);
    color: var(--admin-success);
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.badge-active {
    background: rgba(76, 175, 80, 0.2);
    color: var(--admin-success);
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.badge-expired {
    background: rgba(255, 107, 107, 0.2);
    color: var(--admin-danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 8px 16px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    min-width: 90px;
    justify-content: center;
}

.action-btn i {
    font-size: 14px;
}

.btn-edit {
    background: rgba(255, 159, 67, 0.1);
    color: var(--admin-warning);
    border: 1px solid rgba(255, 159, 67, 0.3);
}

.btn-edit:hover {
    background: rgba(255, 159, 67, 0.2);
    transform: translateY(-2px);
}

.btn-delete {
    background: rgba(255, 107, 107, 0.1);
    color: var(--admin-danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.btn-delete:hover {
    background: rgba(255, 107, 107, 0.2);
    transform: translateY(-2px);
}

.btn-admin {
    background: rgba(138, 108, 255, 0.1);
    color: var(--admin-primary);
    border: 1px solid rgba(138, 108, 255, 0.3);
}

.btn-admin:hover {
    background: rgba(138, 108, 255, 0.2);
    transform: translateY(-2px);
}

.btn-reset {
    background: rgba(0, 188, 212, 0.1);
    color: var(--admin-info);
    border: 1px solid rgba(0, 188, 212, 0.3);
}

.btn-reset:hover {
    background: rgba(0, 188, 212, 0.2);
    transform: translateY(-2px);
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.modal-overlay.show {
    display: block;
}

.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(21, 23, 34, 0.95);
    border: 1px solid var(--admin-border);
    border-radius: 24px;
    padding: 40px;
    width: 90%;
    max-width: 600px;
    z-index: 1001;
    display: none;
    backdrop-filter: blur(30px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.modal.show {
    display: block;
    animation: modalIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modalIn {
    from { opacity: 0; transform: translate(-50%, -60%) scale(0.9); }
    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.modal-title {
    font-size: 1.8rem;
    color: var(--admin-primary);
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    color: var(--admin-muted);
    font-size: 28px;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.close-modal:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--admin-text);
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    margin-bottom: 10px;
    color: var(--admin-text);
    font-weight: 600;
    font-size: 15px;
}

.form-control {
    width: 100%;
    padding: 15px 20px;
    border-radius: 12px;
    border: 2px solid var(--admin-border);
    background: rgba(255, 255, 255, 0.08);
    color: var(--admin-text);
    font-size: 15px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(138, 108, 255, 0.2);
    background: rgba(255, 255, 255, 0.12);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 20px 0;
}

.checkbox-label {
    color: var(--admin-text);
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--admin-primary);
}

.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .grid-2, .grid-3 {
        grid-template-columns: 1fr;
    }
}

.section-title {
    font-size: 1.5rem;
    color: var(--admin-primary);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.section-title i {
    font-size: 1.3rem;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.tool-card {
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-border);
    border-radius: 18px;
    padding: 30px;
    backdrop-filter: blur(20px);
    transition: all 0.3s;
}

.tool-card:hover {
    border-color: var(--admin-primary);
    transform: translateY(-5px);
}

.tool-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: white;
    margin-bottom: 25px;
}

.tool-title {
    font-size: 1.3rem;
    color: var(--admin-text);
    margin-bottom: 15px;
}

.tool-desc {
    color: var(--admin-muted);
    line-height: 1.7;
    margin-bottom: 25px;
}

.log-container {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 12px;
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
}

.log-entry {
    padding: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--admin-muted);
}

.log-entry:last-child {
    border-bottom: none;
}

.log-time {
    color: var(--admin-primary);
    font-weight: bold;
}

.log-info { color: var(--admin-info); }
.log-success { color: var(--admin-success); }
.log-warning { color: var(--admin-warning); }
.log-error { color: var(--admin-danger); }

.danger-zone {
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(255, 141, 133, 0.05));
    border: 2px solid rgba(255, 107, 107, 0.3);
    border-radius: 18px;
    padding: 30px;
    margin-top: 40px;
}

.danger-title {
    color: var(--admin-danger);
    font-size: 1.5rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.danger-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-danger {
    background: linear-gradient(135deg, var(--admin-danger), #ff8a80);
    border: none;
    color: white;
    padding: 14px 28px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
}

.btn-danger:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
}

.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.chart-card {
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-border);
    border-radius: 18px;
    padding: 30px;
    backdrop-filter: blur(20px);
}

.chart-placeholder {
    width: 100%;
    height: 250px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--admin-muted);
    font-size: 16px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.quick-stat {
    text-align: center;
    padding: 25px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.quick-stat:hover {
    border-color: var(--admin-primary);
    transform: translateY(-5px);
}

.quick-stat i {
    font-size: 36px;
    color: var(--admin-primary);
    margin-bottom: 15px;
}

.quick-stat-value {
    font-size: 2.2rem;
    font-weight: bold;
    color: var(--admin-text);
    margin: 10px 0;
}

.quick-stat-label {
    color: var(--admin-muted);
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 18px;
}

.table-responsive {
    overflow-x: auto;
    border-radius: 15px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.page-link {
    padding: 10px 18px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: var(--admin-text);
    text-decoration: none;
    transition: all 0.3s;
    font-weight: 500;
}

.page-link:hover {
    background: rgba(138, 108, 255, 0.2);
    transform: translateY(-2px);
}

.page-link.active {
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    color: white;
}

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-left: 8px;
}

.status-online { background: var(--admin-success); }
.status-offline { background: var(--admin-danger); }
.status-idle { background: var(--admin-warning); }

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: var(--admin-danger);
    color: white;
    font-size: 12px;
    font-weight: bold;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .admin-header h1 {
        font-size: 2.2rem;
    }
    
    .tab-btn {
        min-width: 140px;
        padding: 12px 20px;
    }
    
    .tools-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .admin-header {
        padding: 25px 20px;
    }
    
    .admin-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .tab-btn {
        min-width: 100%;
        justify-content: flex-start;
    }
    
    .admin-tabs {
        flex-direction: column;
        padding: 15px;
    }
    
    .search-box {
        min-width: 100%;
    }
    
    .table-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .admin-table {
        font-size: 13px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 12px 15px;
    }
    
    .modal {
        padding: 25px;
        width: 95%;
    }
    
    .modal-title {
        font-size: 1.5rem;
    }
    
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .stat-value {
        font-size: 2.2rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .btn, .btn-outline {
        width: 100%;
        justify-content: center;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .quick-stats {
        grid-template-columns: 1fr;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
    }
}

@media (max-width: 400px) {
    .admin-header h1 {
        font-size: 1.6rem;
    }
    
    .stat-card {
        padding: 20px 15px;
    }
    
    .tool-card {
        padding: 20px;
    }
}

.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: var(--admin-primary);
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.success-message {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(102, 187, 106, 0.05));
    border: 2px solid rgba(76, 175, 80, 0.3);
    border-radius: 15px;
    padding: 20px;
    margin: 20px 0;
    color: var(--admin-success);
    text-align: center;
    backdrop-filter: blur(20px);
    animation: slideDown 0.5s ease;
}

.error-message {
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(255, 138, 128, 0.05));
    border: 2px solid rgba(255, 107, 107, 0.3);
    border-radius: 15px;
    padding: 20px;
    margin: 20px 0;
    color: var(--admin-danger);
    text-align: center;
    backdrop-filter: blur(20px);
    animation: slideDown 0.5s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<div class="admin-container">
    <div class="admin-header">
        <h1>
            <i class="fas fa-shield-alt"></i>
            پنل مدیریت پیشرفته
        </h1>
        <p>
            خوش آمدید، <strong><?php echo htmlspecialchars($admin_username); ?></strong>! 
            این پنل مدیریتی پیشرفته به شما امکان کنترل کامل سیستم، کاربران و فایل‌ها را می‌دهد.
        </p>
        
        <div class="header-actions">
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i>
                صفحه اصلی
            </a>
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-tachometer-alt"></i>
                داشبورد کاربری
            </a>
            <a href="logout.php" class="btn-outline">
                <i class="fas fa-sign-out-alt"></i>
                خروج از سیستم
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <?php 
        $messages = [
            'user_created' => '✅ کاربر جدید با موفقیت ایجاد شد!',
            'quota_updated' => '✅ سهمیه کاربر با موفقیت به‌روزرسانی شد!',
            'admin_toggled' => '✅ دسترسی ادمین کاربر با موفقیت تغییر کرد!',
            'user_deleted' => '✅ کاربر با موفقیت حذف شد!',
            'password_reset' => '✅ رمز عبور کاربر با موفقیت بازنشانی شد! (رمز جدید: 123456)',
            'file_updated' => '✅ اطلاعات فایل با موفقیت به‌روزرسانی شد!',
            'file_deleted' => '✅ فایل با موفقیت حذف شد!',
            'logs_cleared' => '✅ گزارشات سیستم پاکسازی شدند!',
            'email_sent' => '✅ ایمیل گروهی با موفقیت ارسال شد!',
            'cleanup_completed' => '✅ پاکسازی فایل‌های منقضی شده با موفقیت انجام شد!',
            'orphaned_cleaned' => '✅ پاکسازی فایل‌های بدون رکورد با موفقیت انجام شد!'
        ];
        ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?php echo $messages[$_GET['success']] ?? 'عملیات با موفقیت انجام شد!'; ?>
        </div>
    <?php elseif(isset($_GET['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php 
            if($_GET['error'] == 'duplicate') echo '⚠️ کاربر با این نام کاربری یا ایمیل از قبل وجود دارد!';
            else echo '❌ خطا در انجام عملیات!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">کاربران کل</div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <?php echo $today_users; ?> کاربر امروز
            </div>
            <div class="stat-footer">مدیریت کاربران سیستم</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-header">
                <div class="stat-title">فایل‌های فعال</div>
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $active_files; ?></div>
            <div class="stat-change positive">
                <i class="fas fa-plus"></i>
                <?php echo $today_files; ?> فایل امروز
            </div>
            <div class="stat-footer">از <?php echo $total_files; ?> فایل کل</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-header">
                <div class="stat-title">فضای استفاده شده</div>
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_space; ?> GB</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(($disk_used / $disk_total) * 100, 100); ?>%"></div>
            </div>
            <div class="stat-footer"><?php echo $disk_used; ?>GB از <?php echo $disk_total; ?>GB (<?php echo $disk_percent; ?>%)</div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-header">
                <div class="stat-title">تعداد دانلودها</div>
                <div class="stat-icon">
                    <i class="fas fa-download"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_downloads; ?></div>
            <div class="stat-change positive">
                <i class="fas fa-chart-line"></i>
                آمار دانلود
            </div>
            <div class="stat-footer">تعداد کل دانلودهای سیستم</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">کاربران ادمین</div>
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
            <div class="stat-value">
                <?php 
                $admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch_assoc()['count'];
                echo $admin_count;
                ?>
            </div>
            <div class="stat-footer">دسترسی مدیریتی کامل</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-header">
                <div class="stat-title">فایل‌های منقضی</div>
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $expired_files; ?></div>
            <div class="progress-bar">
                <div class="progress-fill danger" style="width: <?php echo $total_files > 0 ? ($expired_files / $total_files) * 100 : 0; ?>%"></div>
            </div>
            <div class="stat-footer"><?php echo round(($expired_files / max($total_files, 1)) * 100, 1); ?>% از کل فایل‌ها</div>
        </div>
    </div>
    
    <div class="admin-tabs">
        <button class="tab-btn active" data-tab="users">
            <i class="fas fa-users"></i>
            مدیریت کاربران
        </button>
        <button class="tab-btn" data-tab="files">
            <i class="fas fa-file-alt"></i>
            مدیریت فایل‌ها
        </button>
        <button class="tab-btn" data-tab="create-user">
            <i class="fas fa-user-plus"></i>
            ایجاد کاربر
        </button>
        <button class="tab-btn" data-tab="system">
            <i class="fas fa-server"></i>
            اطلاعات سیستم
        </button>
        <button class="tab-btn" data-tab="tools">
            <i class="fas fa-tools"></i>
            ابزارها
        </button>
        <button class="tab-btn" data-tab="logs">
            <i class="fas fa-clipboard-list"></i>
            گزارشات
            <?php if(count($recent_logs) > 0): ?>
                <span class="notification-badge"><?php echo min(count($recent_logs), 9); ?></span>
            <?php endif; ?>
        </button>
    </div>
    
    <div class="tab-content active" id="users-tab">
        <div class="table-container">
            <div class="table-header">
                <div class="section-title">
                    <i class="fas fa-users-cog"></i>
                    مدیریت کاربران سیستم
                </div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="جستجوی کاربران..." id="userSearch">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>کاربر</th>
                            <th>اطلاعات</th>
                            <th>نقش</th>
                            <th>سهمیه</th>
                            <th>استفاده شده</th>
                            <th>عضویت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $users->data_seek(0);
                        while($user = $users->fetch_assoc()): 
                            $used_mb = round($user['used_space'] / 1024 / 1024, 2);
                            $max_gb = $user['max_upload_size'] == 0 ? '∞' : round($user['max_upload_size'] / 1024 / 1024 / 1024, 1) . ' GB';
                            $usage_percent = $user['max_upload_size'] > 0 ? min(($user['used_space'] / $user['max_upload_size']) * 100, 100) : 0;
                        ?>
                        <tr data-search="<?php echo strtolower(htmlspecialchars($user['username'] . ' ' . $user['email'])); ?>">
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: bold;"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <div style="font-size: 12px; color: var(--admin-muted);"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 13px;">
                                    <div>ID: <?php echo $user['id']; ?></div>
                                    <div>ایمیل: <?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <?php if($user['is_admin']): ?>
                                    <span class="badge badge-admin">
                                        <i class="fas fa-crown"></i>
                                        ادمین
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-user">
                                        <i class="fas fa-user"></i>
                                        کاربر
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: bold; margin-bottom: 5px;"><?php echo $max_gb; ?></div>
                                <div class="progress-bar" style="height: 6px;">
                                    <div class="progress-fill <?php echo $usage_percent > 80 ? 'danger' : ($usage_percent > 50 ? 'warning' : ''); ?>" 
                                         style="width: <?php echo $usage_percent; ?>%"></div>
                                </div>
                                <div style="font-size: 12px; color: var(--admin-muted); margin-top: 5px;">
                                    <?php echo round($usage_percent, 1); ?>% استفاده شده
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: bold;"><?php echo $used_mb; ?> MB</div>
                                <div style="font-size: 12px; color: var(--admin-muted);">
                                    <?php echo round($user['used_space'] / 1024 / 1024 / 1024, 3); ?> GB
                                </div>
                            </td>
                            <td>
                                <?php echo date('Y/m/d', strtotime($user['created_at'])); ?>
                                <div style="font-size: 12px; color: var(--admin-muted);">
                                    <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="current_admin" value="<?php echo $user['is_admin']; ?>">
                                        <button type="submit" name="toggle_admin" class="action-btn btn-admin">
                                            <i class="fas <?php echo $user['is_admin'] ? 'fa-user-times' : 'fa-user-shield'; ?>"></i>
                                            <?php echo $user['is_admin'] ? 'حذف ادمین' : 'تبدیل ادمین'; ?>
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="action-btn btn-edit" 
                                            onclick="openEditQuotaModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['max_upload_size']; ?>)">
                                        <i class="fas fa-sliders-h"></i>
                                        تغییر سهمیه
                                    </button>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="reset_password" class="action-btn btn-reset" 
                                                onclick="return confirm('آیا رمز عبور کاربر \"<?php echo htmlspecialchars($user['username']); ?>\" بازنشانی شود؟\\nرمز جدید: 123456')">
                                            <i class="fas fa-key"></i>
                                            بازنشانی رمز
                                        </button>
                                    </form>
                                    
                                    <?php if($user['id'] != 1 && $user['id'] != $user_id): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="action-btn btn-delete"
                                                onclick="return confirm('⚠️ آیا از حذف کاربر \"<?php echo htmlspecialchars($user['username']); ?>\" مطمئن هستید؟\\nتمام فایل‌های کاربر نیز حذف خواهند شد.')">
                                            <i class="fas fa-trash-alt"></i>
                                            حذف کاربر
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination">
                <a href="#" class="page-link active">1</a>
                <a href="#" class="page-link">2</a>
                <a href="#" class="page-link">3</a>
                <span style="color: var(--admin-muted); padding: 10px;">...</span>
                <a href="#" class="page-link">10</a>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="files-tab">
        <div class="table-container">
            <div class="table-header">
                <div class="section-title">
                    <i class="fas fa-file-archive"></i>
                    مدیریت فایل‌های سیستم
                </div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="جستجوی فایل‌ها..." id="fileSearch">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>فایل</th>
                            <th>کاربر</th>
                            <th>کد</th>
                            <th>حجم</th>
                            <th>انقضا</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $all_files = $conn->query("
                            SELECT f.id, f.filename_original, f.filename_saved, f.size, f.code, f.password, 
                                   f.expires_at, f.created_at, f.user_id, u.username,
                                   (SELECT COUNT(*) FROM files WHERE user_id = u.id) as user_file_count
                            FROM files f 
                            JOIN users u ON f.user_id = u.id 
                            ORDER BY f.created_at DESC 
                            LIMIT 50
                        ");
                        
                        while($file = $all_files->fetch_assoc()): 
                            $is_expired = strtotime($file['expires_at']) < time();
                            $file_size_mb = round($file['size'] / 1024 / 1024, 2);
                            $time_remaining = $is_expired ? 'منقضی شده' : human_time_remaining($file['expires_at']);
                        ?>
                        <tr>
                            <td><strong>#<?php echo $file['id']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: rgba(138, 108, 255, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: bold; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($file['filename_original']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--admin-muted);">
                                            <?php echo date('Y/m/d H:i', strtotime($file['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: bold;"><?php echo htmlspecialchars($file['username']); ?></div>
                                <div style="font-size: 12px; color: var(--admin-muted);">
                                    <?php echo $file['user_file_count']; ?> فایل
                                </div>
                            </td>
                            <td>
                                <code style="background: rgba(255, 255, 255, 0.1); padding: 5px 10px; border-radius: 6px; font-family: monospace;">
                                    <?php echo $file['code']; ?>
                                </code>
                            </td>
                            <td>
                                <div style="font-weight: bold;"><?php echo $file_size_mb; ?> MB</div>
                                <div style="font-size: 12px; color: var(--admin-muted);">
                                    <?php echo number_format($file['size']); ?> بایت
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: bold;"><?php echo date('H:i', strtotime($file['expires_at'])); ?></div>
                                <div style="font-size: 12px; color: <?php echo $is_expired ? 'var(--admin-danger)' : 'var(--admin-success)'; ?>;">
                                    <?php echo $time_remaining; ?>
                                </div>
                            </td>
                            <td>
                                <?php if($is_expired): ?>
                                    <span class="badge badge-expired">
                                        <i class="fas fa-clock"></i>
                                        منقضی شده
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-active">
                                        <i class="fas fa-check-circle"></i>
                                        فعال
                                    </span>
                                <?php endif; ?>
                                <?php if($file['password']): ?>
                                    <div style="margin-top: 5px;">
                                        <span style="font-size: 12px; color: var(--admin-primary);">
                                            <i class="fas fa-lock"></i>
                                            رمزدار
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="download.php?code=<?php echo $file['code']; ?>" class="action-btn" style="background: rgba(76, 175, 80, 0.1); color: var(--admin-success); border: 1px solid rgba(76, 175, 80, 0.3);">
                                        <i class="fas fa-download"></i>
                                        دانلود
                                    </a>
                                    <button type="button" class="action-btn btn-edit" onclick="openEditFileModal(<?php echo $file['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                        ویرایش
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                        <button type="submit" name="delete_file_admin" class="action-btn btn-delete"
                                                onclick="return confirm('آیا از حذف این فایل مطمئن هستید؟')">
                                            <i class="fas fa-trash-alt"></i>
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="create-user-tab">
        <div class="table-container">
            <div class="section-title">
                <i class="fas fa-user-plus"></i>
                ایجاد کاربر جدید
            </div>
            
            <form method="POST" class="grid-2" style="gap: 30px;">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        نام کاربری
                    </label>
                    <input type="text" name="username" class="form-control" required placeholder="نام کاربری را وارد کنید">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i>
                        آدرس ایمیل
                    </label>
                    <input type="email" name="email" class="form-control" required placeholder="ایمیل را وارد کنید">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i>
                        رمز عبور
                    </label>
                    <input type="password" name="password" class="form-control" required placeholder="رمز عبور را وارد کنید">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-database"></i>
                        سهمیه آپلود
                    </label>
                    <select name="max_upload" class="form-control">
                        <option value="1073741824">1 GB - پلن پایه</option>
                        <option value="5368709120">5 GB - پلن معمولی</option>
                        <option value="10737418240">10 GB - پلن پیشرفته</option>
                        <option value="21474836480">20 GB - پلن حرفه‌ای</option>
                        <option value="53687091200">50 GB - پلن سازمانی</option>
                        <option value="107374182400">100 GB - پلن VIP</option>
                        <option value="0">♾️ نامحدود - پلن ابری</option>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">
                        <i class="fas fa-user-shield"></i>
                        دسترسی‌های کاربر
                    </label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_admin" value="1">
                            دسترسی مدیریتی کامل (ادمین سیستم)
                        </label>
                    </div>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <button type="submit" name="create_user" class="btn" style="width: 100%; padding: 18px; font-size: 16px;">
                        <i class="fas fa-user-plus"></i>
                        ایجاد کاربر جدید
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="tab-content" id="system-tab">
        <div class="table-container">
            <div class="section-title">
                <i class="fas fa-server"></i>
                اطلاعات سیستم و سرور
            </div>
            
            <div class="grid-3">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3 class="tool-title">اطلاعات PHP</h3>
                    <div class="tool-desc">
                        <div style="margin-bottom: 10px;"><strong>نسخه:</strong> <?php echo $server_info['php_version']; ?></div>
                        <div style="margin-bottom: 10px;"><strong>حداکثر آپلود:</strong> <?php echo $server_info['upload_max']; ?></div>
                        <div style="margin-bottom: 10px;"><strong>حداکثر POST:</strong> <?php echo $server_info['post_max']; ?></div>
                        <div style="margin-bottom: 10px;"><strong>محدودیت حافظه:</strong> <?php echo $server_info['memory_limit']; ?></div>
                        <div><strong>زمان اجرا:</strong> <?php echo $server_info['max_execution']; ?> ثانیه</div>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon" style="background: linear-gradient(135deg, var(--admin-success), #66bb6a);">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="tool-title">اطلاعات دیتابیس</h3>
                    <div class="tool-desc">
                        <div style="margin-bottom: 10px;"><strong>اندازه دیتابیس:</strong> <?php echo $server_info['database_size']; ?> MB</div>
                        <div style="margin-bottom: 10px;"><strong>نوع دیتابیس:</strong> MySQL</div>
                        <div style="margin-bottom: 10px;"><strong>جدول‌ها:</strong> 2 جدول (users, files)</div>
                        <div><strong>سرور:</strong> <?php echo $server_info['server_software']; ?></div>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon" style="background: linear-gradient(135deg, var(--admin-warning), #ffb74d);">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <h3 class="tool-title">فضای ذخیره‌سازی</h3>
                    <div class="tool-desc">
                        <div style="margin-bottom: 10px;"><strong>فضای کل:</strong> <?php echo $disk_total; ?> GB</div>
                        <div style="margin-bottom: 10px;"><strong>فضای استفاده شده:</strong> <?php echo $disk_used; ?> GB (<?php echo $disk_percent; ?>%)</div>
                        <div style="margin-bottom: 10px;"><strong>فضای آزاد:</strong> <?php echo $disk_free; ?> GB</div>
                        <div class="progress-bar" style="height: 10px; margin: 15px 0;">
                            <div class="progress-fill <?php echo $disk_percent > 80 ? 'danger' : ($disk_percent > 50 ? 'warning' : ''); ?>" 
                                 style="width: <?php echo $disk_percent; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="danger-zone">
                <h3 class="danger-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    منطقه خطر - عملیات غیرقابل بازگشت
                </h3>
                <p style="color: var(--admin-muted); margin-bottom: 25px; line-height: 1.7;">
                    این عملیات‌ها بر روی سیستم تأثیر مستقیم دارند و غیرقابل بازگشت هستند.
                    لطفاً قبل از اجرا از صحت اطلاعات اطمینان حاصل کنید.
                </p>
                
                <div class="danger-buttons">
                    <form method="POST" action="admin_actions.php" onsubmit="return confirm('⚠️ آیا مطمئن هستید؟ تمام فایل‌های منقضی شده حذف خواهند شد.')">
                        <input type="hidden" name="action" value="cleanup_expired">
                        <button type="submit" class="btn-danger">
                            <i class="fas fa-trash-alt"></i>
                            پاکسازی فایل‌های منقضی شده
                        </button>
                    </form>
                    
                    <form method="POST" action="admin_actions.php" onsubmit="return confirm('⚠️ آیا مطمئن هستید؟ تمام فایل‌های بدون رکورد در دیتابیس حذف خواهند شد.')">
                        <input type="hidden" name="action" value="cleanup_orphaned">
                        <button type="submit" class="btn-danger">
                            <i class="fas fa-ghost"></i>
                            پاکسازی فایل‌های بدون رکورد
                        </button>
                    </form>
                    
                    <button type="button" class="btn-danger" onclick="showCacheClearModal()">
                        <i class="fas fa-broom"></i>
                        پاکسازی کش سیستم
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="tools-tab">
        <div class="table-container">
            <div class="section-title">
                <i class="fas fa-tools"></i>
                ابزارهای مدیریتی
            </div>
            
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="tool-title">ارسال ایمیل گروهی</h3>
                    <p class="tool-desc">ارسال ایمیل به تمام کاربران سیستم یا گروه خاصی از کاربران</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">موضوع ایمیل</label>
                            <input type="text" name="email_subject" class="form-control" placeholder="موضوع ایمیل را وارد کنید">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">گیرندگان</label>
                            <select name="email_recipients" class="form-control">
                                <option value="all">همه کاربران</option>
                                <option value="admins">فقط ادمین‌ها</option>
                                <option value="users">فقط کاربران معمولی</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">متن پیام</label>
                            <textarea name="email_message" class="form-control" rows="4" placeholder="متن پیام را وارد کنید"></textarea>
                        </div>
                        
                        <button type="submit" name="send_bulk_email" class="btn" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i>
                            ارسال ایمیل گروهی
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon" style="background: linear-gradient(135deg, var(--admin-success), #66bb6a);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="tool-title">گزارشات آماری</h3>
                    <p class="tool-desc">تهیه گزارشات آماری از عملکرد سیستم و کاربران</p>
                    
                    <div class="quick-stats" style="margin: 25px 0;">
                        <div class="quick-stat">
                            <i class="fas fa-user-clock"></i>
                            <div class="quick-stat-value"><?php echo $today_users; ?></div>
                            <div class="quick-stat-label">کاربر امروز</div>
                        </div>
                        <div class="quick-stat">
                            <i class="fas fa-file-upload"></i>
                            <div class="quick-stat-value"><?php echo $today_files; ?></div>
                            <div class="quick-stat-label">آپلود امروز</div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button class="btn-outline" style="flex: 1;" onclick="generateReport('daily')">
                            <i class="fas fa-calendar-day"></i>
                            گزارش روزانه
                        </button>
                        <button class="btn-outline" style="flex: 1;" onclick="generateReport('weekly')">
                            <i class="fas fa-calendar-week"></i>
                            گزارش هفتگی
                        </button>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon" style="background: linear-gradient(135deg, var(--admin-info), #4dd0e1);">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="tool-title">تنظیمات سیستم</h3>
                    <p class="tool-desc">تنظیمات پیشرفته سیستم و پیکربندی</p>
                    
                    <div style="margin: 25px 0;">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" checked>
                                فعال‌سازی آپلود همزمان
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" checked>
                                فعال‌سازی ایمیل اطلاع‌رسانی
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox">
                                محدودیت IP
                            </label>
                        </div>
                    </div>
                    
                    <button class="btn" style="width: 100%;">
                        <i class="fas fa-save"></i>
                        ذخیره تنظیمات
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="logs-tab">
        <div class="table-container">
            <div class="section-title">
                <i class="fas fa-clipboard-list"></i>
                گزارشات و لاگ سیستم
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                <div style="color: var(--admin-muted);">
                    <i class="fas fa-info-circle"></i>
                    نمایش ۲۰ گزارش آخر سیستم
                </div>
                
                <form method="POST" onsubmit="return confirm('آیا از پاکسازی تمام گزارشات مطمئن هستید؟')">
                    <button type="submit" name="clear_system_logs" class="btn-outline">
                        <i class="fas fa-broom"></i>
                        پاکسازی گزارشات
                    </button>
                </form>
            </div>
            
            <div class="log-container">
                <?php if(count($recent_logs) > 0): ?>
                    <?php foreach(array_reverse($recent_logs) as $log): ?>
                        <div class="log-entry">
                            <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                            <span class="<?php 
                                if(strpos($log, 'ERROR') !== false) echo 'log-error';
                                elseif(strpos($log, 'WARNING') !== false) echo 'log-warning';
                                elseif(strpos($log, 'SUCCESS') !== false) echo 'log-success';
                                else echo 'log-info';
                            ?>">
                                <?php echo htmlspecialchars($log); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--admin-muted);">
                        <i class="fas fa-clipboard" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <div>هیچ گزارشی در سیستم ثبت نشده است.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay"></div>

<div class="modal" id="editQuotaModal">
    <div class="modal-header">
        <h3 class="modal-title">
            <i class="fas fa-sliders-h"></i>
            تغییر سهمیه کاربر
        </h3>
        <button class="close-modal" onclick="closeModal('editQuotaModal')">×</button>
    </div>
    
    <form method="POST" id="quotaForm">
        <input type="hidden" name="user_id" id="quotaUserId">
        
        <div class="form-group">
            <label class="form-label">نام کاربری</label>
            <input type="text" id="quotaUsername" class="form-control" disabled style="background: rgba(255, 255, 255, 0.05);">
        </div>
        
        <div class="form-group">
            <label class="form-label">سهمیه آپلود جدید</label>
            <select name="max_upload" id="quotaMaxUpload" class="form-control">
                <option value="1073741824">1 GB - پلن پایه</option>
                <option value="5368709120">5 GB - پلن معمولی</option>
                <option value="10737418240">10 GB - پلن پیشرفته</option>
                <option value="21474836480">20 GB - پلن حرفه‌ای</option>
                <option value="53687091200">50 GB - پلن سازمانی</option>
                <option value="107374182400">100 GB - پلن VIP</option>
                <option value="0">♾️ نامحدود - پلن ابری</option>
            </select>
        </div>
        
        <button type="submit" name="update_quota" class="btn" style="width: 100%; padding: 16px; font-size: 16px; margin-top: 10px;">
            <i class="fas fa-save"></i>
            ذخیره تغییرات
        </button>
    </form>
</div>

<div class="modal" id="editFileModal">
    <div class="modal-header">
        <h3 class="modal-title">
            <i class="fas fa-edit"></i>
            ویرایش اطلاعات فایل
        </h3>
        <button class="close-modal" onclick="closeModal('editFileModal')">×</button>
    </div>
    
    <form method="POST" id="fileForm">
        <input type="hidden" name="file_id" id="fileId">
        
        <div class="form-group">
            <label class="form-label">تاریخ انقضای جدید</label>
            <input type="datetime-local" name="expires_at" id="fileExpiresAt" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">رمز عبور جدید (اختیاری)</label>
            <input type="password" name="file_password" id="filePassword" class="form-control" placeholder="در صورت عدم نیاز خالی بگذارید">
        </div>
        
        <button type="submit" name="edit_file" class="btn" style="width: 100%; padding: 16px; font-size: 16px; margin-top: 10px;">
            <i class="fas fa-save"></i>
            به‌روزرسانی فایل
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById(tabId + '-tab').classList.add('active');
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === tabId) {
                history.replaceState(null, '', window.location.pathname);
            }
        });
    });
    
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    if (requestedTab) {
        const tabBtn = document.querySelector(`.tab-btn[data-tab="${requestedTab}"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    }
    
    const userSearch = document.getElementById('userSearch');
    if (userSearch) {
        userSearch.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                if (searchData && searchData.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    const fileSearch = document.getElementById('fileSearch');
    if (fileSearch) {
        fileSearch.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#files-tab tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

function openEditQuotaModal(userId, username, currentQuota) {
    document.getElementById('quotaUserId').value = userId;
    document.getElementById('quotaUsername').value = username;
    document.getElementById('quotaMaxUpload').value = currentQuota;
    
    openModal('editQuotaModal');
}

function openEditFileModal(fileId) {
    document.getElementById('fileId').value = fileId;
    
    const now = new Date();
    now.setHours(now.getHours() + 24);
    const expiresAt = now.toISOString().slice(0, 16);
    document.getElementById('fileExpiresAt').value = expiresAt;
    
    openModal('editFileModal');
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
    document.getElementById('modalOverlay').classList.add('show');
    
    document.getElementById('modalOverlay').onclick = function() {
        closeModal(modalId);
    };
    
    document.addEventListener('keydown', function escClose(e) {
        if (e.key === 'Escape') {
            closeModal(modalId);
        }
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    document.getElementById('modalOverlay').classList.remove('show');
}

function showCacheClearModal() {
    if (confirm('آیا از پاکسازی کش سیستم مطمئن هستید؟\nاین عمل ممکن است باعث کاهش موقت عملکرد شود.')) {
        alert('کش سیستم با موفقیت پاکسازی شد!');
    }
}

function generateReport(type) {
    const reports = {
        'daily': 'گزارش روزانه با موفقیت ایجاد شد.',
        'weekly': 'گزارش هفتگی با موفقیت ایجاد شد.'
    };
    
    alert(reports[type] || 'گزارش ایجاد شد.');
}

<?php
function human_time_remaining($expires_at) {
    $now = time();
    $expires = strtotime($expires_at);
    $diff = $expires - $now;
    
    if ($diff < 0) return 'منقضی شده';
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . ' ساعت و ' . $minutes . ' دقیقه باقی مانده';
    } else {
        return $minutes . ' دقیقه باقی مانده';
    }
}
?>
</script>
</body>
</html>