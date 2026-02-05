<?php
session_start();
require_once "db.php";

$code = $_GET['code'] ?? '';
if (empty($code)) {
    die("کد وارد نشده است.");
}

$conn = db();

$stmt = $conn->prepare("SELECT id, filename_original, filename_saved, password, expires_at, user_id, size, created_at FROM files WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$stmt->bind_result($id, $original, $saved, $hashed_password, $expires, $file_user_id, $size, $created_at);
$stmt->fetch();
$stmt->close();

if (!$id) {
    echo "<!DOCTYPE html>
    <html lang='fa' dir='rtl'>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>خطا | Legion Transfer</title>
    <link rel='stylesheet' href='assets/css/style.css'>
    <style>
        .error-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(10px);
            margin-top: 5px;
        }
        
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ff6b6b;
            animation: glitch 0.5s infinite;
        }
        
        .back-btn {
            margin-top: 30px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .back-btn:hover::before {
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
        
        @media(max-width: 768px) {
            .error-card {
                margin-top: 5px;
                padding: 30px 20px;
            }
            
            .back-btn {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
        
        @media(max-width: 480px) {
            .error-card {
                margin-top: 5px;
                padding: 20px 15px;
            }
            
            .error-icon {
                font-size: 48px;
            }
            
            .back-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
    </head>
    <body>
    
    <div class='error-container'>
        <div class='error-card'>
            <div class='error-icon'>❌</div>
            <h2>فایل پیدا نشد!</h2>
            <p>کد وارد شده معتبر نیست یا فایل حذف شده است.</p>
            <a href='index.php' class='back-btn'>بازگشت به صفحه اصلی</a>
        </div>
    </div>
    
    </body>
    </html>";
    exit;
}

$current_time = time();
$expires_time = strtotime($expires);

if ($expires_time < $current_time) {
    $file_path = __DIR__ . "/uploads/" . $saved;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    $conn->query("DELETE FROM files WHERE id = $id");
    
    if ($file_user_id) {
        $conn->query("UPDATE users SET used_space = used_space - (SELECT size FROM files WHERE id = $id) WHERE id = $file_user_id");
    }
    
    echo "<!DOCTYPE html>
    <html lang='fa' dir='rtl'>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>منقضی شده | Legion Transfer</title>
    <link rel='stylesheet' href='assets/css/style.css'>
    <style>
        .error-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(10px);
            margin-top: 5px;
        }
        
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ff9f43;
            animation: glitch 0.5s infinite;
        }
        
        .back-btn {
            margin-top: 30px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .back-btn:hover::before {
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
    
    <div class='error-container'>
        <div class='error-card'>
            <div class='error-icon'>⏰</div>
            <h2>فایل منقضی شده!</h2>
            <p>زمان دانلود این فایل به پایان رسیده است.</p>
            <a href='index.php' class='back-btn'>بازگشت به صفحه اصلی</a>
        </div>
    </div>
    
    </body>
    </html>";
    exit;
}

$error = '';
$password_verified = false;

if ($hashed_password) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $password = $_POST['password'] ?? '';
        if (password_verify($password, $hashed_password)) {
            $password_verified = true;
        } else {
            $error = "پسورد وارد شده صحیح نیست.";
        }
    }
    
    if (!$password_verified) {
        echo "<!DOCTYPE html>
        <html lang='fa' dir='rtl'>
        <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>ورود پسورد | Legion Transfer</title>
        <link rel='stylesheet' href='assets/css/style.css'>
        <style>
            .password-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .password-card {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 20px;
                padding: 40px;
                max-width: 400px;
                width: 100%;
                backdrop-filter: blur(10px);
            }
            
            .password-icon {
                font-size: 48px;
                margin-bottom: 20px;
                text-align: center;
                color: var(--accent);
                animation: glitch 0.5s infinite;
            }
            
            .file-info {
                background: rgba(255, 255, 255, 0.05);
                border-radius: 10px;
                padding: 15px;
                margin: 20px 0;
                text-align: center;
            }
            
            .password-form {
                margin-top: 30px;
            }
            
            .password-input {
                width: 100%;
                padding: 15px 20px;
                border-radius: 12px;
                border: 2px solid rgba(255, 255, 255, 0.2);
                background: rgba(255, 255, 255, 0.08);
                color: var(--text);
                font-size: 16px;
                margin-bottom: 20px;
            }
            
            .password-input:focus {
                outline: none;
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(138, 108, 255, 0.2);
            }
            
            .password-btn {
                width: 100%;
                padding: 15px;
                border-radius: 12px;
                border: none;
                background: linear-gradient(135deg, var(--accent), var(--accent2));
                color: white;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s;
                position: relative;
                overflow: hidden;
            }
            
            .password-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(138, 108, 255, 0.4);
            }
            
            .password-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }
            
            .password-btn:hover::before {
                left: 100%;
            }
            
            .error-message {
                background: rgba(255, 107, 107, 0.1);
                border: 1px solid rgba(255, 107, 107, 0.3);
                border-radius: 10px;
                padding: 12px;
                margin-bottom: 20px;
                color: #ff6b6b;
                text-align: center;
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
            
            .toggle-password:hover {
                color: var(--accent);
            }
            
            .password-input-wrapper {
                position: relative;
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
        
        <div class='password-container'>
            <div class='password-card'>
                <div class='password-icon'>🔒</div>
                <h2 style='text-align: center;'>فایل محافظت شده</h2>
                
                <div class='file-info'>
                    <strong>$original</strong>
                    <p style='color: rgba(255, 255, 255, 0.7); margin-top: 5px;'>برای دانلود نیاز به پسورد دارید</p>
                </div>";
                
                if ($error) {
                    echo "<div class='error-message'>$error</div>";
                }
                
                echo "<form method='POST' class='password-form'>
                    <div class='password-input-wrapper'>
                        <input type='password' name='password' placeholder='پسورد را وارد کنید' class='password-input' id='passwordInput' required>
                        <button type='button' class='toggle-password' id='togglePassword'>👁️</button>
                    </div>
                    <button type='submit' class='password-btn'>ورود و مشاهده فایل</button>
                </form>
            </div>
        </div>
        
        <script>
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('passwordInput');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        </script>
        
        </body>
        </html>";
        exit;
    }
}

if (!$hashed_password || $password_verified) {
    $size_mb = round($size / 1024 / 1024, 2);
    $created_date = date('Y/m/d H:i', strtotime($created_at));
    $expires_date = date('Y/m/d H:i', strtotime($expires));
    $time_left = strtotime($expires) - time();
    $hours_left = floor($time_left / 3600);
    $minutes_left = floor(($time_left % 3600) / 60);
    
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    $icon = getFileIcon($ext);
    
    echo "<!DOCTYPE html>
    <html lang='fa' dir='rtl'>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>دانلود فایل | Legion Transfer</title>
    <link rel='stylesheet' href='assets/css/style.css'>
    <style>
        .download-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .download-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        .file-icon-large {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--accent);
            display: inline-block;
        }
        
        .file-info-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            text-align: right;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .info-value {
            color: white;
            font-weight: 500;
        }
        
        .download-button {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: white;
            padding: 18px 40px;
            border-radius: 15px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .download-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(138, 108, 255, 0.4);
        }
        
        .download-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .download-button:hover::before {
            left: 100%;
        }
        
        .back-button {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 15px 30px;
            border-radius: 15px;
            text-decoration: none;
            margin-top: 15px;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .password-protected {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #ffc107;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .time-left {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #4CAF50;
        }
        
        @media(max-width: 768px) {
            .download-card {
                padding: 30px 20px;
                margin-top: 20px;
            }
            
            .file-info-section {
                padding: 20px;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
                padding: 15px 0;
            }
            
            .download-button {
                padding: 16px 30px;
                font-size: 16px;
                width: 100%;
                box-sizing: border-box;
            }
            
            .back-button {
                padding: 14px 25px;
                width: 100%;
                box-sizing: border-box;
            }
            
            .file-icon-large {
                font-size: 56px;
            }
        }
        
        @media(max-width: 480px) {
            .download-card {
                padding: 25px 15px;
            }
            
            .file-info-section {
                padding: 15px;
            }
            
            .info-label {
                font-size: 13px;
            }
            
            .info-value {
                font-size: 14px;
            }
            
            .download-button {
                padding: 15px;
                font-size: 15px;
            }
            
            .file-icon-large {
                font-size: 48px;
            }
        }
    </style>
    </head>
    <body>
    
    <div class='download-container'>
        <div class='download-card'>
            <div class='file-icon-large'>$icon</div>
            <h1 style='margin-bottom: 10px;'>$original</h1>
            <p style='color: rgba(255, 255, 255, 0.7); margin-bottom: 30px;'>فایل آماده دانلود</p>
            
            <div class='file-info-section'>
                <div class='info-item'>
                    <span class='info-label'>حجم فایل:</span>
                    <span class='info-value'>$size_mb MB</span>
                </div>
                <div class='info-item'>
                    <span class='info-label'>نوع فایل:</span>
                    <span class='info-value'>" . strtoupper($ext) . " فایل</span>
                </div>
                <div class='info-item'>
                    <span class='info-label'>کد فایل:</span>
                    <span class='info-value'>$code</span>
                </div>
                <div class='info-item'>
                    <span class='info-label'>تاریخ آپلود:</span>
                    <span class='info-value'>$created_date</span>
                </div>
                <div class='info-item'>
                    <span class='info-label'>تاریخ انقضا:</span>
                    <span class='info-value'>$expires_date</span>
                </div>
            </div>
            
            <div class='time-left'>
                ⏰ زمان باقی‌مانده: $hours_left ساعت و $minutes_left دقیقه
            </div>
            
            " . ($hashed_password ? "<div class='password-protected'>🔒 این فایل با پسورد محافظت شده است</div>" : "") . "
            
            <a href='download_file.php?code=$code' class='download-button'>
                <span>📥</span>
                <span>دانلود فایل</span>
            </a>
            
            <div style='margin-top: 30px;'>
                <a href='index.php' class='back-button'>بازگشت به صفحه اصلی</a>
            </div>
        </div>
    </div>
    
    </body>
    </html>";
    exit;
}

function getFileIcon($ext) {
    $ext = strtolower($ext);
    $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
    $document_exts = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
    $archive_exts = ['zip', 'rar', '7z', 'tar', 'gz'];
    $video_exts = ['mp4', 'avi', 'mkv', 'mov', 'wmv'];
    $audio_exts = ['mp3', 'wav', 'ogg', 'flac'];
    
    if (in_array($ext, $image_exts)) return '🖼️';
    if (in_array($ext, $document_exts)) return '📄';
    if (in_array($ext, $archive_exts)) return '📦';
    if (in_array($ext, $video_exts)) return '🎬';
    if (in_array($ext, $audio_exts)) return '🎵';
    if ($ext == 'exe' || $ext == 'msi') return '⚙️';
    
    return '📁';
}
?>