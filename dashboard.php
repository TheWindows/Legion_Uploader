<?php
session_start();
require_once "db.php";
cleanup_expired_files();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$conn = db();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, used_space, max_upload_size, is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $used, $max_upload, $is_admin);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT id, filename_original, code, expires_at, created_at, password 
    FROM files 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_files = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>داشبورد | Legion Transfer</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
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
    .dashboard-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 20px;
        backdrop-filter: blur(10px);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 0 22px rgba(138, 108, 255, 0.25);
        border-color: rgba(138, 108, 255, 0.45);
    }
    
    .stat-title {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: bold;
        color: white;
    }
    
    .upload-section {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 40px;
        backdrop-filter: blur(10px);
        margin-bottom: 30px;
    }
    
    .recent-files {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 30px;
        backdrop-filter: blur(10px);
        margin-bottom: 30px;
    }
    
    .file-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .file-item:last-child {
        border-bottom: none;
    }
    
    .back-btn {
        position: fixed;
        top: 20px;
        left: 20px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        z-index: 100;
    }
    
    .back-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    .nav-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .nav-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s;
    }
    
    .nav-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    .progress-bar {
        height: 10px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
        margin-top: 10px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #8a6cff, #7c7cff);
        border-radius: 5px;
        transition: width 0.3s;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        font-size: 15px;
    }
    
    .custom-file-input {
        position: relative;
        width: 100%;
    }
    
    .file-input-wrapper {
        position: relative;
        width: 100%;
        cursor: pointer;
    }
    
    .file-input-wrapper input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    
    .file-input-design {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 20px;
        border: 2px dashed rgba(255, 255, 255, 0.3);
        border-radius: 15px;
        background: rgba(255, 255, 255, 0.05);
        transition: all 0.3s;
        min-height: 60px;
    }
    
    .file-input-design:hover {
        border-color: var(--accent);
        background: rgba(138, 108, 255, 0.1);
    }
    
    .file-input-design.has-file {
        border-color: #4CAF50;
        background: rgba(76, 175, 80, 0.1);
    }
    
    .file-icon {
        font-size: 24px;
        color: var(--accent);
    }
    
    .file-text {
        color: rgba(255, 255, 255, 0.9);
        font-size: 16px;
    }
    
    .file-name {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        margin-top: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .custom-select {
        position: relative;
        width: 100%;
    }
    
    .custom-select select {
        width: 100%;
        padding: 15px 20px;
        padding-left: 50px;
        border-radius: 15px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08);
        color: var(--text);
        font-size: 15px;
        cursor: pointer;
        appearance: none;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .custom-select select:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(138, 108, 255, 0.2);
        background: rgba(255, 255, 255, 0.12);
    }
    
    .custom-select::after {
        content: "⌄";
        position: absolute;
        top: 25%;
        left: 20px;
        transform: translateY(-50%);
        color: var(--accent);
        font-size: 20px;
        pointer-events: none;
    }
    
    .password-toggle-wrapper {
        position: relative;
        width: 100%;
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .toggle-switch {
        position: relative;
        width: 60px;
        height: 30px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .toggle-switch.active {
        background: var(--accent);
    }
    
    .toggle-slider {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        transition: all 0.3s;
    }
    
    .toggle-switch.active .toggle-slider {
        left: 33px;
    }
    
    .password-input-wrapper {
        position: relative;
        width: 100%;
        display: none;
    }
    
    .password-input-wrapper.show {
        display: block;
    }
    
    .password-input-wrapper input {
        width: 100%;
        padding: 15px 20px;
        padding-left: 50px;
        border-radius: 15px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08);
        color: var(--text);
        font-size: 15px;
        transition: all 0.3s;
    }
    
    .password-input-wrapper input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(138, 108, 255, 0.2);
        background: rgba(255, 255, 255, 0.12);
    }
    
    .toggle-password {
        position: absolute;
        left: 5px;
        top: 35%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        font-size: 18px;
    }
    
    .upload-button {
        width: 100%;
        padding: 18px;
        border-radius: 15px;
        border: none;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .upload-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(138, 108, 255, 0.4);
    }
    
    .upload-button:active {
        transform: translateY(0);
    }
    
    .button-icon {
        font-size: 18px;
    }
    
    .download-section {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
        backdrop-filter: blur(10px);
    }
    
    .download-form {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .download-input {
        flex: 1;
        padding: 15px 20px;
        border-radius: 12px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08);
        color: var(--text);
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .download-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(138, 108, 255, 0.2);
    }
    
    .download-btn {
        padding: 15px 30px;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        height: 44px;
    }
    
    .download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(138, 108, 255, 0.4);
    }
    
    .file-actions {
        display: flex;
        gap: 8px;
    }
    
    .delete-btn {
        background: rgba(255, 107, 107, 0.1);
        border: 1px solid rgba(255, 107, 107, 0.3);
        color: #ff6b6b;
        padding: 5px 15px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 12px;
        transition: all 0.3s;
    }
    
    .delete-btn:hover {
        background: rgba(255, 107, 107, 0.2);
        transform: translateY(-2px);
    }
    
    .expired-badge {
        background: rgba(255, 159, 67, 0.1);
        border: 1px solid rgba(255, 159, 67, 0.3);
        color: #ff9f43;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 11px;
        margin-right: 10px;
    }

    @media(max-width: 900px) {
        .dashboard-container {
            padding: 15px;
            margin: 30px auto;
        }
        
        .dashboard-header {
            flex-direction: column;
            gap: 20px;
            text-align: center;
            padding: 25px 20px;
        }
        
        .stats-cards {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            padding: 18px 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
        }
        
        .stat-title {
            font-size: 13px;
        }
        
        .upload-section {
            padding: 30px 25px;
        }
        
        .recent-files {
            padding: 25px 20px;
        }
        
        .file-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
            padding: 18px 0;
        }
        
        .file-actions {
            width: 100%;
            justify-content: flex-end;
        }
        
        .nav-buttons {
            justify-content: center;
        }
        
        .nav-btn {
            padding: 12px 25px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .file-input-design {
            padding: 18px 15px;
            flex-direction: column;
            text-align: center;
            min-height: 70px;
        }
        
        .file-text {
            font-size: 15px;
        }
        
        .custom-select select {
            padding: 14px 18px;
            padding-left: 45px;
            font-size: 14.5px;
        }
        
        .custom-select::after {
            left: 15px;
            font-size: 18px;
        }
        
        .password-toggle-wrapper {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .toggle-switch {
            width: 55px;
            height: 28px;
        }
        
        .toggle-slider {
            width: 22px;
            height: 22px;
            top: 3px;
            left: 3px;
        }
        
        .toggle-switch.active .toggle-slider {
            left: 30px;
        }
        
        .password-input-wrapper input {
            padding: 14px 18px;
            padding-left: 45px;
            font-size: 14.5px;
        }
        
        .upload-button {
            padding: 16px;
            font-size: 15px;
        }
        
        .button-icon {
            font-size: 17px;
        }
        
        .download-section {
            padding: 25px 20px;
        }
        
        .download-form {
            flex-direction: column;
            gap: 12px;
        }
        
        .download-input {
            padding: 14px 18px;
            font-size: 15px;
            text-align: center;
        }
        
        .download-btn {
            width: 100%;
            padding: 14px 20px;
            font-size: 15px;
            justify-content: center;
            height: auto;
        }
        
        .back-btn {
            position: relative;
            top: auto;
            left: auto;
            margin-bottom: 20px;
            display: inline-block;
        }
    }

    @media(max-width: 768px) {
        .dashboard-container {
            margin: 25px auto;
            padding: 12px;
        }
        
        .stats-cards {
            grid-template-columns: 1fr;
            max-width: 500px;
            margin: 0 auto 25px;
        }
        
        .stat-card {
            padding: 20px;
        }
        
        .stat-value {
            font-size: 30px;
        }
        
        .upload-section {
            padding: 25px 20px;
        }
        
        .recent-files {
            padding: 22px 18px;
        }
        
        .file-item {
            gap: 12px;
        }
        
        .delete-btn {
            padding: 6px 18px;
            font-size: 13px;
        }
        
        .file-input-design {
            padding: 20px 15px;
            gap: 10px;
        }
        
        .file-icon {
            font-size: 22px;
        }
        
        .custom-select select {
            padding: 13px 16px;
            padding-left: 40px;
            font-size: 14px;
        }
        
        .custom-select::after {
            left: 12px;
            font-size: 16px;
        }
        
        .password-toggle-wrapper {
            gap: 12px;
        }
        
        .password-input-wrapper input {
            padding: 13px 16px;
            padding-left: 40px;
            font-size: 14px;
        }
        
        .upload-button {
            padding: 15px;
            font-size: 14.5px;
        }
        
        .download-section {
            padding: 22px 18px;
        }
        
        .download-input {
            padding: 13px 16px;
            font-size: 14.5px;
        }
        
        .download-btn {
            padding: 13px 18px;
            font-size: 14.5px;
        }
    }

    @media(max-width: 480px) {
        .dashboard-container {
            margin: 20px auto;
            padding: 10px;
        }
        
        .dashboard-header {
            padding: 20px 15px;
            border-radius: 12px;
        }
        
        .stat-card {
            padding: 18px 15px;
            border-radius: 12px;
        }
        
        .stat-value {
            font-size: 26px;
        }
        
        .stat-title {
            font-size: 12px;
        }
        
        .upload-section {
            padding: 20px 15px;
            border-radius: 12px;
        }
        
        .recent-files {
            padding: 20px 15px;
            border-radius: 12px;
        }
        
        .file-item {
            padding: 15px 0;
        }
        
        .nav-buttons {
            gap: 8px;
        }
        
        .nav-btn {
            padding: 10px 18px;
            font-size: 13px;
            border-radius: 20px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-label {
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .file-input-design {
            padding: 18px 12px;
            border-radius: 12px;
        }
        
        .file-text {
            font-size: 14px;
        }
        
        .file-name {
            font-size: 13px;
        }
        
        .custom-select select {
            padding: 12px 15px;
            padding-left: 35px;
            font-size: 13.5px;
            border-radius: 12px;
        }
        
        .custom-select::after {
            left: 10px;
            font-size: 15px;
        }
        
        .toggle-switch {
            width: 50px;
            height: 26px;
        }
        
        .toggle-slider {
            width: 20px;
            height: 20px;
            top: 3px;
            left: 3px;
        }
        
        .toggle-switch.active .toggle-slider {
            left: 27px;
        }
        
        .password-input-wrapper input {
            padding: 12px 15px;
            padding-left: 35px;
            font-size: 13.5px;
            border-radius: 12px;
        }
        
        .upload-button {
            padding: 14px;
            font-size: 14px;
            border-radius: 12px;
        }
        
        .button-icon {
            font-size: 16px;
        }
        
        .download-section {
            padding: 20px 15px;
            border-radius: 12px;
        }
        
        .download-input {
            padding: 12px 15px;
            font-size: 14px;
            border-radius: 10px;
        }
        
        .download-btn {
            padding: 12px 16px;
            font-size: 14px;
            border-radius: 10px;
        }
        
        .delete-btn {
            padding: 5px 12px;
            font-size: 11px;
            border-radius: 15px;
        }
        
        .expired-badge {
            font-size: 10px;
            padding: 2px 8px;
            margin-right: 8px;
        }
    }

    @media(max-width: 360px) {
        .stats-cards {
            gap: 12px;
        }
        
        .stat-card {
            padding: 15px 12px;
        }
        
        .stat-value {
            font-size: 24px;
        }
        
        .upload-section {
            padding: 18px 12px;
        }
        
        .recent-files {
            padding: 18px 12px;
        }
        
        .file-input-design {
            padding: 16px 10px;
        }
        
        .file-text {
            font-size: 13px;
        }
        
        .custom-select select {
            padding: 11px 13px;
            padding-left: 30px;
            font-size: 13px;
        }
        
        .custom-select::after {
            left: 8px;
            font-size: 14px;
        }
        
        .upload-button {
            padding: 13px;
            font-size: 13.5px;
        }
        
        .download-input {
            padding: 11px 13px;
            font-size: 13.5px;
        }
        
        .download-btn {
            padding: 11px 15px;
            font-size: 13.5px;
        }
    }
</style>
</head>
<body>

<a href="index.php" class="back-btn">← صفحه اصلی</a>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div>
            <h1>خوش آمدید، <?php echo htmlspecialchars($username); ?>!</h1>
            <p><?php echo round($used / 1024 / 1024, 2); ?> MB از 
            <?php 
            if ($max_upload == 0) {
                echo '∞';
            } else {
                echo round($max_upload / 1024 / 1024 / 1024, 1) . ' GB';
            }
            ?></p>
        </div>
        <div>
            <?php if($is_admin): ?>
            <a href="admin.php" class="nav-btn">پنل ادمین</a>
            <?php endif; ?>
            <a href="logout.php" class="nav-btn logout">خروج</a>
        </div>
    </div>
    
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-title">فضای استفاده شده</div>
            <div class="stat-value"><?php echo round($used / 1024 / 1024, 2); ?> MB</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php 
                if ($max_upload == 0) {
                    echo '100';
                } else {
                    echo min(($used / $max_upload) * 100, 100);
                }
                ?>%"></div>
            </div>
            <p><?php echo round($used / 1024 / 1024, 2); ?> MB از 
            <?php 
            if ($max_upload == 0) {
                echo '∞';
            } else {
                echo round($max_upload / 1024 / 1024 / 1024, 1) . ' GB';
            }
            ?></p>
        </div>
        
        <div class="stat-card">
            <div class="stat-title">فایل‌های اخیر</div>
            <div class="stat-value"><?php echo count($recent_files); ?></div>
            <p>آخرین فایل‌های شما</p>
        </div>
    </div>
    
    <div class="upload-section">
        <h2>آپلود فایل جدید</h2>
        <p style="color: rgba(255, 255, 255, 0.7); margin-bottom: 30px;">فایل خود را انتخاب کنید و تنظیمات را اعمال نمایید</p>
        
        <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label class="form-label">انتخاب فایل</label>
                <div class="custom-file-input">
                    <div class="file-input-wrapper">
                        <input type="file" name="file" id="fileInput" required accept="*/*">
                        <div class="file-input-design" id="fileInputDesign">
                            <span class="file-icon">📁</span>
                            <div>
                                <div class="file-text">برای انتخاب فایل کلیک کنید یا فایل را اینجا بکشید</div>
                                <div class="file-name" id="fileName">هیچ فایلی انتخاب نشده</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">پسورد اختیاری</label>
                <div class="password-toggle-wrapper">
                    <div class="toggle-switch" id="passwordToggle">
                        <div class="toggle-slider"></div>
                    </div>
                    <span>فعال‌سازی پسورد</span>
                </div>
                <div class="password-input-wrapper" id="passwordInputWrapper">
                    <div style="position: relative;">
                        <input type="password" name="password" id="passwordInput" placeholder="پسورد را وارد کنید">
                        <button type="button" class="toggle-password" id="showPassword">👁️</button>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">زمان خودکار حذف</label>
                <div class="custom-select">
                    <select name="timer">
                        <option value="5m">5 دقیقه</option>
                        <option value="1h">1 ساعت</option>
                        <option value="3h">3 ساعت</option>
                        <option value="12h">12 ساعت</option>
                        <option value="24h">24 ساعت</option>
                    </select>
                </div>
            </div>
            
            <button class="upload-button" type="submit">
                <span class="button-icon">📤</span>
                <span>آپلود فایل</span>
            </button>
        </form>
        
        <div class="nav-buttons">
            <a href="myfiles.php" class="nav-btn">مشاهده همه فایل‌ها</a>
            <a href="index.php" class="nav-btn">صفحه اصلی</a>
        </div>
    </div>
    
    <?php if (count($recent_files) > 0): ?>
    <div class="recent-files">
        <h2>فایل‌های اخیر</h2>
        <?php foreach ($recent_files as $file): 
            $is_expired = strtotime($file['expires_at']) < time();
        ?>
        <div class="file-item">
            <div>
                <strong><?php echo htmlspecialchars($file['filename_original']); ?></strong>
                <?php if($is_expired): ?>
                    <span class="expired-badge">منقضی شده</span>
                <?php endif; ?>
                <p style="font-size: 12px; color: rgba(255, 255, 255, 0.7); margin-top: 5px;">
                    کد: <?php echo htmlspecialchars($file['code']); ?> | 
                    انقضا: <?php echo date('H:i', strtotime($file['expires_at'])); ?> |
                    <?php echo $file['password'] ? '🔒 محافظت شده' : '🔓 بدون پسورد'; ?>
                </p>
            </div>
            <div class="file-actions">
                <?php if(!$is_expired): ?>
                <a href="download.php?code=<?php echo urlencode($file['code']); ?>" class="nav-btn" style="padding: 5px 15px; font-size: 12px;">
                    دانلود
                </a>
                <?php endif; ?>
                <a href="delete_file.php?id=<?php echo $file['id']; ?>&code=<?php echo $file['code']; ?>" 
                   class="delete-btn" 
                   onclick="return confirm('آیا از حذف این فایل مطمئن هستید؟')">
                    حذف
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (count($recent_files) == 5): ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="myfiles.php" class="nav-btn">مشاهده همه فایل‌ها →</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="download-section">
    <h3>دانلود فایل با کد</h3>
    <p style="color: rgba(255, 255, 255, 0.7); margin-bottom: 20px;">کد فایل را وارد کنید و دانلود نمایید</p>
    
    <form id="dashboardDownloadForm" class="download-form">
        <input type="text" id="dashboardFileCode" placeholder="کد 7 رقمی فایل" required class="download-input">
        <button type="submit" class="download-btn">
            <span>🔍</span>
            <span>مشاهده فایل</span>
        </button>
    </form>
    
    <div id="dashboardDownloadResult" style="margin-top: 20px;"></div>
</div>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const fileInputDesign = document.getElementById('fileInputDesign');
    const fileName = document.getElementById('fileName');
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInputWrapper = document.getElementById('passwordInputWrapper');
    const passwordInput = document.getElementById('passwordInput');
    const showPassword = document.getElementById('showPassword');
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            fileName.textContent = `${file.name} (${sizeMB} MB)`;
            fileInputDesign.classList.add('has-file');
        } else {
            fileName.textContent = 'هیچ فایلی انتخاب نشده';
            fileInputDesign.classList.remove('has-file');
        }
    });
    
    fileInputDesign.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--accent)';
        this.style.background = 'rgba(138, 108, 255, 0.15)';
    });
    
    fileInputDesign.addEventListener('dragleave', function() {
        this.style.borderColor = '';
        this.style.background = '';
    });
    
    fileInputDesign.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.background = '';
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
    });
    
    passwordToggle.addEventListener('click', function() {
        this.classList.toggle('active');
        if (this.classList.contains('active')) {
            passwordInputWrapper.classList.add('show');
            passwordInput.required = false;
        } else {
            passwordInputWrapper.classList.remove('show');
            passwordInput.required = false;
            passwordInput.value = '';
        }
    });
    
    showPassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '🙈';
    });
    
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        const fileInput = this.querySelector('input[type="file"]');
        const file = fileInput.files[0];
        
        if (!file) {
            e.preventDefault();
            alert('لطفاً یک فایل انتخاب کنید.');
            fileInputDesign.style.borderColor = '#ff6b6b';
            setTimeout(() => {
                fileInputDesign.style.borderColor = '';
            }, 2000);
            return false;
        }
        
        const submitBtn = this.querySelector('.upload-button');
        submitBtn.innerHTML = '<span class="button-icon">⏳</span><span>در حال آپلود...</span>';
        submitBtn.disabled = true;
    });
    
    document.getElementById('dashboardDownloadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const code = document.getElementById('dashboardFileCode').value.trim();
        const resultDiv = document.getElementById('dashboardDownloadResult');
        
        if (!code || code.length !== 7 || isNaN(code)) {
            resultDiv.innerHTML = `
                <div style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 10px; padding: 15px; color: #ff6b6b; text-align: center;">
                    کد باید یک عدد 7 رقمی باشد
                </div>
            `;
            return;
        }
        
        window.location.href = 'download.php?code=' + code;
    });
</script>
</body>
</html>