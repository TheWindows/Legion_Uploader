<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت نام | Legion Transfer</title>
<link rel="stylesheet" href="assets/css/auth.css">
<style>
    .auth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
    }
    
    .auth-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .auth-header h2 {
        color: white;
        margin-bottom: 10px;
        font-size: 28px;
    }
    
    .auth-header p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
    }
    
    .input-group {
        margin-bottom: 20px;
    }
    
    .input-group label {
        display: block;
        color: white;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
    }
    
    .input-group input {
        width: 90%;
        padding: 15px 20px;
        border-radius: 12px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08);
        color: white;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .input-group input:focus {
        outline: none;
        border-color: #8a6cff;
        box-shadow: 0 0 0 3px rgba(138, 108, 255, 0.2);
        background: rgba(255, 255, 255, 0.12);
    }
    
    .auth-btn {
        width: 100%;
        padding: 15px;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, #8a6cff, #7c7cff);
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
        position: relative;
        overflow: hidden;
    }
    
    .auth-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(138, 108, 255, 0.4);
    }
    
    .auth-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    
    .auth-btn:hover::before {
        left: 100%;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .auth-footer p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
    }
    
    .auth-footer a {
        color: #8a6cff;
        text-decoration: none;
        font-weight: 500;
    }
    
    .auth-footer a:hover {
        text-decoration: underline;
    }
    
    .emoji-container {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .main-emoji {
        font-size: 64px;
        display: inline-block;
        animation: glitch 0.5s infinite;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .main-emoji:hover {
        animation: glitch-hover 0.3s infinite;
        transform: scale(1.1);
        filter: brightness(1.2);
    }
    
    @keyframes glitch {
        0% { transform: translate(0); }
        20% { transform: translate(-2px, 2px); }
        40% { transform: translate(-2px, -2px); }
        60% { transform: translate(2px, 2px); }
        80% { transform: translate(2px, -2px); }
        100% { transform: translate(0); }
    }
    
    @keyframes glitch-hover {
        0% { transform: scale(1.1) translate(0); filter: brightness(1.2); }
        25% { transform: scale(1.1) translate(-3px, 3px); filter: brightness(1.2) hue-rotate(10deg); }
        50% { transform: scale(1.1) translate(3px, -3px); filter: brightness(1.2) hue-rotate(-10deg); }
        75% { transform: scale(1.1) translate(-3px, -3px); filter: brightness(1.2) hue-rotate(20deg); }
        100% { transform: scale(1.1) translate(0); filter: brightness(1.2); }
    }
    
    .password-toggle {
        position: absolute;
        left: 8px;
        top: 47%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        font-size: 18px;
        transition: color 0.3s;
    }
    
    .password-toggle:hover {
        color: #8a6cff;
    }
    
    .password-wrapper {
        position: relative;
    }
    
    .error-message {
        color: #ff6b6b;
        text-align: center;
        margin-top: 15px;
        padding: 10px;
        background: rgba(255, 107, 107, 0.1);
        border-radius: 8px;
        border: 1px solid rgba(255, 107, 107, 0.3);
        font-size: 14px;
    }
</style>
</head>
<body>

<div class="auth-page">
  <div class="auth-card">
    <div class="emoji-container">
        <div class="main-emoji" onclick="changeEmoji()">📝</div>
    </div>
    
    <div class="auth-header">
      <h2>ثبت نام</h2>
      <p>حساب جدید بسازید و تا 1GB فایل آپلود کنید</p>
    </div>

    <form action="register_process.php" method="POST">
      <div class="input-group">
        <label>نام کاربری</label>
        <input type="text" name="username" required>
      </div>
      <div class="input-group">
        <label>ایمیل</label>
        <input type="email" name="email" required>
      </div>
      <div class="input-group">
        <label>پسورد</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="passwordInput" required>
            <button type="button" class="password-toggle" id="togglePassword">👁️</button>
        </div>
      </div>
      <button class="auth-btn" type="submit">ثبت نام</button>
    </form>
    
    <?php if(isset($_GET['error']) && $_GET['error'] == "duplicate"): ?>
        <div class="error-message">
            نام کاربری یا ایمیل از قبل وجود دارد.
        </div>
    <?php elseif(isset($_GET['error']) && $_GET['error'] == "failed"): ?>
        <div class="error-message">
            ثبت نام با خطا مواجه شد. لطفاً دوباره تلاش کنید.
        </div>
    <?php endif; ?>

    <div class="auth-footer">
      <p>قبلا ثبت نام کرده‌اید؟ <a href="login.php">ورود</a></p>
    </div>
  </div>
</div>

<script>
    const emoji = document.querySelector('.main-emoji');
    const emojis = ['📝', '✏️', '📋', '📄', '📊', '📈', '🎯', '🚀'];
    let emojiIndex = 0;
    
    function changeEmoji() {
        emojiIndex = (emojiIndex + 1) % emojis.length;
        emoji.textContent = emojis[emojiIndex];
    }
    
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '🙈';
    });
    
    setInterval(() => {
        if (!document.querySelector('.main-emoji:hover')) {
            emojiIndex = (emojiIndex + 1) % emojis.length;
            emoji.textContent = emojis[emojiIndex];
        }
    }, 3000);
</script>

</body>
</html>