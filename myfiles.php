<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = db();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, filename_original, size, password, code, expires_at, created_at 
    FROM files 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$files = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT username, used_space, max_upload_size FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $used_space, $max_upload_size);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فایل‌های من | Legion Transfer</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    .files-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        backdrop-filter: blur(10px);
    }
    
    .upload-btn {
        background: #4a6cf7;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .files-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .file-card {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 20px;
        backdrop-filter: blur(10px);
    }
    
    .file-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .file-name {
        font-weight: bold;
        font-size: 16px;
        color: white;
    }
    
    .file-size {
        background: rgba(255, 255, 255, 0.1);
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 12px;
    }
    
    .file-info {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .info-label {
        color: rgba(255, 255, 255, 0.7);
    }
    
    .info-value {
        color: white;
        font-family: monospace;
    }
    
    .copy-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        cursor: pointer;
        margin-top: 10px;
        width: 100%;
        transition: all 0.3s;
    }
    
    .copy-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .back-btn {
        position: fixed;
        top: 10px;
        left: 20px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        z-index: 100;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .expired {
        opacity: 0.6;
        border-color: #ff6b6b;
    }
    
    .expired-badge {
        background: #ff6b6b;
        color: white;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 11px;
        margin-right: 10px;
    }
</style>
</head>
<body>

<a href="dashboard.php" class="back-btn">← بازگشت</a>

<div class="files-container">
    <div class="header-bar">
        <div>
            <h1>فایل‌های من</h1>
            <p>سلام <?php echo htmlspecialchars($username); ?>! فضای استفاده شده: <?php echo round($used_space / 1024 / 1024, 2); ?> MB / 
            <?php 
            if ($max_upload_size == 0) {
                echo '∞ MB';
            } else {
                echo round($max_upload_size / 1024 / 1024, 2) . ' MB';
            }
            ?></p>
        </div>
        <a href="upload.php" class="upload-btn">
            <span>+ آپلود فایل جدید</span>
        </a>
    </div>
    
    <?php if (count($files) > 0): ?>
        <div class="files-grid">
            <?php foreach ($files as $file): 
                $is_expired = strtotime($file['expires_at']) < time();
            ?>
                <div class="file-card <?php echo $is_expired ? 'expired' : ''; ?>">
                    <div class="file-header">
                        <div class="file-name">
                            <?php if ($is_expired): ?>
                                <span class="expired-badge">منقضی شده</span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($file['filename_original']); ?>
                        </div>
                        <div class="file-size"><?php echo round($file['size'] / 1024 / 1024, 2); ?> MB</div>
                    </div>
                    
                    <div class="file-info">
                        <div class="info-row">
                            <span class="info-label">کد:</span>
                            <span class="info-value"><?php echo htmlspecialchars($file['code']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">لینک دانلود:</span>
                            <span class="info-value">
                                <?php if (!$is_expired): ?>
                                    <a href="download.php?code=<?php echo urlencode($file['code']); ?>" style="color: #4a6cf7;">
                                        دانلود فایل
                                    </a>
                                <?php else: ?>
                                    <span style="color: #ff6b6b;">منقضی شده</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">پسورد:</span>
                            <span class="info-value">
                                <?php echo $file['password'] ? '✅ دارد' : '❌ ندارد'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاریخ انقضا:</span>
                            <span class="info-value <?php echo $is_expired ? 'expired-text' : ''; ?>">
                                <?php echo date('Y/m/d H:i', strtotime($file['expires_at'])); ?>
                                <?php if ($is_expired): ?>
                                    (منقضی شده)
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">آپلود شده:</span>
                            <span class="info-value">
                                <?php echo date('Y/m/d H:i', strtotime($file['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <button class="copy-btn" onclick="copyToClipboard('<?php echo $file['code']; ?>')">
                        کپی کد
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h2>هنوز فایلی آپلود نکرده‌اید</h2>
            <p>اولین فایل خود را آپلود کنید و کد دریافت نمایید</p>
            <a href="upload.php" class="upload-btn" style="margin-top: 20px; display: inline-block;">
                <span>+ شروع آپلود</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    function copyToClipboard(code) {
        navigator.clipboard.writeText(code).then(() => {
            alert('کد کپی شد: ' + code);
        });
    }
</script>
</body>
</html>