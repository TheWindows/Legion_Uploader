<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        die("خطا در آپلود فایل. کد خطا: " . $_FILES['file']['error']);
    }
    
    $timer = isset($_POST['timer']) ? $_POST['timer'] : '1h';
    $password = isset($_POST['password']) && trim($_POST['password']) !== '' ? password_hash(trim($_POST['password']), PASSWORD_BCRYPT) : null;
    
    $file = $_FILES['file'];
    $originalName = $file['name'];
    $size = $file['size'];
    $tmpPath = $file['tmp_name'];
    
    $conn = db();
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT used_space, max_upload_size FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($used_space, $max_upload);
    $stmt->fetch();
    $stmt->close();

    if ($max_upload > 0 && ($used_space + $size) > $max_upload) {
        $remaining = $max_upload - $used_space;
        if ($remaining <= 0) {
            die("فضای حساب شما پر شده است. لطفاً فایل‌های قدیمی را حذف کنید.");
        } else {
            die("حجم فایل نمی‌تواند بیشتر از " . round($remaining / 1024 / 1024, 2) . "MB باشد.");
        }
    }
    
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $uniqueName = "file_" . bin2hex(random_bytes(10)) . "." . $ext;
    $destination = $uploadDir . $uniqueName;
    
    $chunk_size = 8192;
    if (file_exists($tmpPath)) {
        $src = fopen($tmpPath, 'rb');
        $dest = fopen($destination, 'wb');
        
        if ($src && $dest) {
            while (!feof($src)) {
                fwrite($dest, fread($src, $chunk_size));
            }
            fclose($src);
            fclose($dest);
        } else {
            die("خطا در ذخیره فایل. لطفاً اطمینان حاصل کنید پوشه uploads وجود دارد و دسترسی نوشتن دارد.");
        }
    } else {
        die("فایل آپلود شده پیدا نشد.");
    }
    
    $warning = '';
    $ext_lower = strtolower($ext);
    $dangerous_extensions = ['exe', 'bat', 'cmd', 'vbs', 'js', 'dll', 'so', 'bin', 'appx', 'msi', 'phar', 'phtml', 'php3', 'php4', 'php5', 'php6', 'php7', 'phps'];
    
    if (in_array($ext_lower, $dangerous_extensions)) {
        $warning = '<div style="background: rgba(255,159,67,0.1); border: 1px solid rgba(255,159,67,0.3); border-radius: 10px; padding: 10px; margin: 10px 0; color: #ff9f43;">
        ⚠️ توجه: این یک فایل اجرایی است. فقط فایل‌های از منابع معتبر را اجرا کنید.
        </div>';
    }
    
    $code = strval(rand(1000000, 9999999));
    
    $current_time = time();
    switch($timer) {
        case '5m':
            $expires_at = date("Y-m-d H:i:s", $current_time + (5 * 60));
            $timer_text = "5 دقیقه";
            break;
        case '1h':
            $expires_at = date("Y-m-d H:i:s", $current_time + (60 * 60));
            $timer_text = "1 ساعت";
            break;
        case '3h':
            $expires_at = date("Y-m-d H:i:s", $current_time + (3 * 60 * 60));
            $timer_text = "3 ساعت";
            break;
        case '12h':
            $expires_at = date("Y-m-d H:i:s", $current_time + (12 * 60 * 60));
            $timer_text = "12 ساعت";
            break;
        case '24h':
            $expires_at = date("Y-m-d H:i:s", $current_time + (24 * 60 * 60));
            $timer_text = "24 ساعت";
            break;
        default:
            $expires_at = date("Y-m-d H:i:s", $current_time + (60 * 60));
            $timer_text = "1 ساعت";
    }
    
    $user_id = $_SESSION['user_id'];
    $conn = db();
    
    $query = "INSERT INTO files (user_id, filename_original, filename_saved, size, password, code, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ississs", $user_id, $originalName, $uniqueName, $size, $password, $code, $expires_at);
    
    if ($stmt->execute()) {
        $conn->query("UPDATE users SET used_space = used_space + $size WHERE id = $user_id");
        
        $download_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/download.php?code=" . $code;
        
        echo "<!DOCTYPE html>
        <html lang='fa' dir='rtl'>
        <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>آپلود موفق | Legion Transfer</title>
        <link rel='stylesheet' href='assets/css/style.css'>
        <style>
            .success-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .success-card {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 20px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                backdrop-filter: blur(10px);
            }
            .success-icon {
                font-size: 64px;
                margin-bottom: 20px;
                text-align: center;
                animation: glitch 0.5s infinite;
                color: #4CAF50;
            }
            
            .info-box {
                background: rgba(255, 255, 255, 0.1);
                border: 2px solid var(--accent);
                border-radius: 15px;
                padding: 25px;
                margin: 20px 0;
            }
            
            .info-item {
                margin: 15px 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .info-label {
                color: rgba(255, 255, 255, 0.7);
                min-width: 120px;
            }
            
            .info-value {
                color: var(--accent);
                font-weight: bold;
                font-family: monospace;
                font-size: 18px;
            }
            
            .link-box {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                padding: 15px;
                margin: 20px 0;
                word-break: break-all;
                font-family: monospace;
                font-size: 14px;
                color: rgba(255, 255, 255, 0.9);
            }
            
            .btn-group {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 30px;
                flex-wrap: wrap;
            }
            
            .copy-btn {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                color: white;
                padding: 10px 20px;
                border-radius: 25px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 10px 0;
                transition: all 0.3s;
                position: relative;
                overflow: hidden;
            }
            
            .copy-btn:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateY(-2px);
            }
            
            .copy-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }
            
            .copy-btn:hover::before {
                left: 100%;
            }
            
            .code-display {
                font-family: monospace;
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                padding: 20px;
                background: rgba(138, 108, 255, 0.1);
                border-radius: 10px;
                margin: 20px 0;
                letter-spacing: 3px;
                color: var(--accent);
                border: 2px dashed var(--accent);
            }
            
            @keyframes glitch {
                0% { transform: translate(0); }
                20% { transform: translate(-2px, 2px); }
                40% { transform: translate(-2px, -2px); }
                60% { transform: translate(2px, 2px); }
                80% { transform: translate(2px, -2px); }
                100% { transform: translate(0); }
            }
        </style>
        </head>
        <body>
        
        <div class='success-container'>
            <div class='success-card'>
                <div class='success-icon'>✅</div>
                <h2 style='text-align: center; margin-bottom: 30px;'>آپلود موفقیت‌آمیز!</h2>
                
                {$warning}
                
                <div class='info-box'>
                    <div class='info-item'>
                        <span class='info-label'>نام فایل:</span>
                        <span class='info-value'>$originalName</span>
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>حجم فایل:</span>
                        <span class='info-value'>" . round($size / 1024 / 1024, 2) . " MB</span>
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>کد فایل:</span>
                        <span class='info-value'>$code</span>
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>انقضا:</span>
                        <span class='info-value'>$timer_text (" . date('H:i', strtotime($expires_at)) . ")</span>
                    </div>
                </div>
                
                <h4>کد فایل:</h4>
                <div class='code-display'>$code</div>
                
                <h4>لینک مستقیم دانلود:</h4>
                <div class='link-box'>$download_link</div>
                
                <button class='copy-btn' onclick='copyToClipboard(\"$code\")'>
                    <span>📋</span>
                    <span>کپی کد</span>
                </button>
                
                <button class='copy-btn' onclick='copyToClipboard(\"$download_link\")'>
                    <span>🔗</span>
                    <span>کپی لینک</span>
                </button>
                
                <div class='btn-group'>
                    <a href='dashboard.php' class='btn'>برگشت به داشبورد</a>
                    <a href='myfiles.php' class='btn-outline'>مشاهده فایل‌ها</a>
                    <a href='index.php#download' class='btn-outline'>دانلود فایل دیگر</a>
                </div>
            </div>
        </div>
        
        <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('متن کپی شد!');
                });
            }
        </script>
        
        </body>
        </html>";
    } else {
        @unlink($destination);
        die("خطا در ذخیره‌سازی اطلاعات در پایگاه داده: " . $conn->error);
    }
    
    $stmt->close();
} else {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آپلود فایل | Legion Transfer</title>
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
        .upload-container {
            max-width: 600px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        
        .upload-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
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
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .file-input-wrapper {
            position: relative;
            width: 100%;
            cursor: pointer;
            margin-bottom: 25px;
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
            padding: 30px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
            min-height: 80px;
        }
        
        .file-input-design:hover {
            border-color: var(--accent);
            background: rgba(138, 108, 255, 0.1);
        }
        
        .file-icon {
            font-size: 28px;
            color: var(--accent);
            animation: glitch 0.5s infinite;
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
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        
        .custom-select {
            position: relative;
            width: 100%;
        }
        
        .custom-select select {
            width: 100%;
            padding: 15px 20px;
            border-radius: 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: var(--text);
            font-size: 15px;
            cursor: pointer;
            appearance: none;
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
        }
        
        .password-icon {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 18px;
        }
        
        .toggle-password {
            position: absolute;
            left: 5px;
            top: 40%;
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
            position: relative;
            overflow: hidden;
        }
        
        .upload-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(138, 108, 255, 0.4);
        }
        
        .upload-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .upload-button:hover::before {
            left: 100%;
        }
        
        @keyframes glitch {
            0% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
            100% { transform: translate(0); }
        }
    </style>
    </head>
    <body>
    
    <a href="dashboard.php" class="back-btn">← بازگشت</a>
    
    <div class="upload-container">
        <div class="upload-card">
            <h2>آپلود فایل جدید</h2>
            <p style="color: rgba(255, 255, 255, 0.7); margin-bottom: 30px;">فایل خود را انتخاب کنید و تنظیمات را اعمال نمایید</p>
            
            <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label class="form-label">انتخاب فایل</label>
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
                    <span>📤</span>
                    <span>آپلود فایل</span>
                </button>
            </form>
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
            submitBtn.innerHTML = '<span>⏳</span><span>در حال آپلود...</span>';
            submitBtn.disabled = true;
        });
    </script>
    
    </body>
    </html>
    <?php
}
?>