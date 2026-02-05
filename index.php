<?php
session_start();
require_once "db.php";

$username = "";
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $conn = db();
    $stmt = $conn->prepare("SELECT username, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($db_username, $db_is_admin);
    $stmt->fetch();
    $username = $db_username;
    $is_admin = $db_is_admin;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Legion Upload</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    .profile-menu {
        position: relative;
        display: inline-block;
    }
    .logo-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 24px rgba(138, 108, 255, 0.25);
        position: relative;
        overflow: hidden;
        background: none;
    }

    .logo-icon::before {
        display: none; 
    }

    .logo-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: inherit;
    }
    .profile-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .profile-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .profile-popup {
        position: absolute;
        top: 100%;
        left: 0;
        background: rgba(16, 17, 26);
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        padding: 15px;
        min-width: 200px;
        display: none;
        z-index: 1000;
        margin-top: 10px;
    }
    
    .profile-popup.show {
        display: block;
    }
    
    .profile-popup::before {
        content: '';
        position: absolute;
        top: -10px;
        left: 20px;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-bottom: 10px solid rgba(16, 17, 26);
    }
    
    .popup-item {
        padding: 10px;
        cursor: pointer;
        border-radius: 5px;
        transition: background 0.3s;
        color: #ffffffff;
    }
    
    .popup-item:hover {
        background: rgba(7, 7, 7, 0.95)
    }
    
    .popup-item.logout {
        color: #e74c3c;
    }
    
    .popup-header {
        padding: 10px;
        border-bottom: 1px solid #eee;
        margin-bottom: 10px;
        color: #ffffffff;
        font-weight: bold;
    }
    
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
    }
    
    .overlay.show {
        display: block;
    }
    
    .code-popup {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 15px;
        padding: 30px;
        min-width: 300px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        display: none;
        z-index: 1001;
    }
    
    .code-popup.show {
        display: block;
    }
    
    .code-display {
        font-family: monospace;
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        margin: 20px 0;
        letter-spacing: 3px;
        color: #333;
    }
    
    .close-btn {
        position: absolute;
        top: 10px;
        left: 10px;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #666;
    }

    .mobile-menu-btn {
        display: none;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 4px;
        z-index: 1001;
    }

    .mobile-menu-btn span {
        width: 20px;
        height: 2px;
        background: white;
        transition: all 0.3s;
        border-radius: 2px;
    }

    .mobile-menu-btn.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .mobile-menu-btn.active span:nth-child(2) {
        opacity: 0;
    }

    .mobile-menu-btn.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
    }

    .mobile-nav {
        position: fixed;
        top: 0;
        left: -100%;
        width: 80%;
        max-width: 300px;
        height: 100vh;
        background: rgba(16, 17, 26, 0.95);
        backdrop-filter: blur(20px);
        border-right: 1px solid var(--border);
        padding: 80px 20px 30px;
        z-index: 1000;
        transition: left 0.3s ease;
        overflow-y: auto;
    }

    .mobile-nav.show {
        left: 0;
    }

    .mobile-nav-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
    }

    .mobile-nav-overlay.show {
        display: block;
    }

    .mobile-nav-header {
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 20px;
    }

    .mobile-nav-header .logo {
        font-size: 20px;
        color: var(--accent);
        font-weight: bold;
    }

    .mobile-profile-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .mobile-profile-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(138, 108, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        font-weight: bold;
    }

    .mobile-profile-name {
        font-weight: bold;
        color: var(--text);
    }

    .mobile-profile-email {
        font-size: 12px;
        color: var(--muted);
    }

    @media(max-width: 900px){
        .nav-right {
            display: none;
        }
        
        .mobile-menu-btn {
            display: flex;
        }
        
        .hero {
            flex-direction: column;
            padding-top: 120px;
            padding-left: 20px;
            padding-right: 20px;
        }
        
        .hero-right {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .grid {
            grid-template-columns: 1fr;
        }
        
        .card.pricing {
            max-width: 400px;
            margin: 0 auto;
            text-align: center;
        }
        
        .card.pricing .price-number {
            font-size: 48px;
            margin: 20px 0;
        }
        
        .card.pricing .price-list {
            margin: 25px 0;
            text-align: center;
            padding: 0 20px;
        }
        
        .card.pricing .price-list div {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .card.pricing .price-list div:last-child {
            border-bottom: none;
        }
        
        .card.pricing .btn {
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
            display: block;
        }
        
        .files-grid {
            grid-template-columns: 1fr;
        }
        
        .header-bar, .dashboard-header {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        
        .stats-cards {
            grid-template-columns: 1fr;
        }
        
        .nav-buttons {
            justify-content: center;
        }
    }

    @media(max-width: 768px) {
        .hero-left h1 {
            font-size: 2.5rem;
            text-align: center;
        }
        
        .hero-left p {
            text-align: center;
            font-size: 1rem;
        }
        
        .hero-buttons {
            justify-content: center;
        }
        
        .hero-stats {
            justify-content: center;
        }
        
        .stat {
            width: 120px;
            text-align: center;
        }
        
        .section-title h2 {
            text-align: center;
            font-size: 2rem;
        }
        
        .section-title p {
            text-align: center;
        }
        
        .steps {
            flex-direction: column;
        }
        
        .step {
            text-align: center;
        }
        
        .step-number {
            margin: 0 auto 15px;
        }
    }

    @media(max-width: 480px) {
        .hero-left h1 {
            font-size: 2rem;
        }
        
        .stat {
            width: 100px;
            padding: 10px;
        }
        
        .stat-num {
            font-size: 1.1rem;
        }
        
        .stat-text {
            font-size: 0.8rem;
        }
        
        .card.pricing {
            padding: 25px 15px;
        }
        
        .card.pricing .price-number {
            font-size: 40px;
        }
        
        .files-container, .dashboard-container {
            padding: 10px;
        }
        
        .header-bar h1 {
            font-size: 1.5rem;
        }
    }

    .card.pricing {
        background: linear-gradient(135deg, rgba(138, 108, 255, 0.1), rgba(124, 124, 255, 0.05));
        border: 1px solid rgba(138, 108, 255, 0.3);
        padding: 40px 30px;
        position: relative;
        overflow: hidden;
    }

    .card.pricing::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        right: -50%;
        bottom: -50%;
        background: radial-gradient(circle at center, rgba(138, 108, 255, 0.15), transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 0.8; }
    }

    .price-title {
        font-size: 28px;
        font-weight: bold;
        color: var(--accent);
        margin-bottom: 10px;
        position: relative;
        z-index: 2;
    }

    .price-number {
        font-size: 56px;
        font-weight: bold;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin: 20px 0;
        text-shadow: 0 0 30px rgba(138, 108, 255, 0.3);
        position: relative;
        z-index: 2;
    }

    .price-text {
        color: var(--muted);
        font-size: 16px;
        margin-bottom: 30px;
        position: relative;
        z-index: 2;
    }

    .price-features {
        margin: 30px 0;
        position: relative;
        z-index: 2;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .feature-item:last-child {
        border-bottom: none;
    }

    .feature-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        font-size: 16px;
    }

    .feature-text {
        color: var(--text);
        font-size: 15px;
    }

    .price-action {
        margin-top: 30px;
        position: relative;
        z-index: 2;
    }

    .price-btn {
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        border: none;
        color: white;
        padding: 16px 30px;
        font-size: 16px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        margin-bottom: 15px;
        position: relative;
        overflow: hidden;
    }

    .price-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .price-btn:hover::before {
        left: 100%;
    }

    .btn-icon {
        font-size: 18px;
    }

    .price-note {
        color: var(--muted);
        font-size: 14px;
        text-align: center;
        margin-top: 10px;
    }

    .download-section {
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 40px;
        margin: 60px auto;
        max-width: 800px;
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
        border: 2px solid var(--border);
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

    @media(max-width: 768px) {
        .download-form {
            flex-direction: column;
            gap: 12px;
        }
        
        .download-input {
            width: 100%;
            padding: 14px 18px;
            font-size: 15px;
            text-align: center;
        }
        
        .download-btn {
            width: 100%;
            padding: 14px 20px;
            font-size: 15px;
            justify-content: center;
        }
        
        .download-section {
            padding: 25px 20px;
            margin: 40px auto;
        }
        
        .download-section h3 {
            text-align: center;
            font-size: 1.3rem;
        }
    }

    @media(max-width: 480px) {
        .download-input {
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .download-btn {
            padding: 13px 18px;
            font-size: 14px;
            height: auto;
        }
        
        .download-section {
            padding: 20px 15px;
            margin: 30px auto;
        }
    }

    @media(max-width: 900px) {
    .grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        max-width: 900px;
        margin: 0 auto;
    }
    
    .card.pricing {
        max-width: 100%;
        padding: 35px 25px;
        margin: 0;
    }
    
    .price-number {
        font-size: 50px;
        margin: 25px 0;
    }
    
    .price-title {
        font-size: 26px;
    }
    
    .price-text {
        font-size: 16px;
        margin-bottom: 25px;
    }
    
    .price-features {
        margin: 25px 0;
    }
    
    .feature-item {
        padding: 14px 0;
    }
    
    .feature-icon {
        width: 36px;
        height: 36px;
        font-size: 16px;
        flex-shrink: 0;
    }
    
    .feature-text {
        font-size: 15px;
        text-align: right;
        flex: 1;
    }
    
    .price-btn {
        padding: 18px 30px;
        font-size: 16px;
        min-height: 56px;
    }
}

@media(max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
        max-width: 600px;
        gap: 30px;
    }
    
    .card.pricing {
        padding: 30px 25px;
        width: 100%;
    }
    
    .price-number {
        font-size: 48px;
    }
    
    .price-title {
        font-size: 24px;
    }
    
    .price-features {
        margin: 30px 0;
    }
    
    .feature-item {
        padding: 12px 0;
        align-items: flex-start;
    }
    
    .feature-text {
        font-size: 14.5px;
        line-height: 1.5;
    }
    
    .price-action {
        margin-top: 35px;
    }
}

@media(max-width: 600px) {
    .grid {
        max-width: 500px;
        gap: 25px;
    }
    
    .card.pricing {
        padding: 28px 22px;
    }
    
    .price-number {
        font-size: 44px;
        margin: 20px 0;
    }
    
    .price-features {
        margin: 25px 0;
    }
    
    .feature-item {
        padding: 11px 0;
        gap: 15px;
    }
    
    .feature-icon {
        width: 34px;
        height: 34px;
        font-size: 15px;
    }
    
    .feature-text {
        font-size: 14px;
    }
    
    .price-btn {
        padding: 16px 25px;
        font-size: 15px;
        min-height: 52px;
    }
}

@media(max-width: 480px) {
    .grid {
        max-width: 400px;
        gap: 20px;
    }
    
    .card.pricing {
        padding: 25px 20px;
    }
    
    .price-number {
        font-size: 40px;
        margin: 18px 0;
    }
    
    .price-title {
        font-size: 22px;
        margin-bottom: 8px;
    }
    
    .price-text {
        font-size: 15px;
        margin-bottom: 20px;
    }
    
    .price-features {
        margin: 20px 0;
    }
    
    .feature-item {
        padding: 10px 0;
        gap: 12px;
    }
    
    .feature-icon {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .feature-text {
        font-size: 13.5px;
    }
    
    .price-btn {
        padding: 15px 22px;
        font-size: 14.5px;
        min-height: 50px;
    }
    
    .btn-icon {
        font-size: 17px;
    }
    
    .price-note {
        font-size: 13px;
        margin-top: 8px;
    }
}

@media(max-width: 380px) {
    .grid {
        max-width: 350px;
        gap: 18px;
    }
    
    .card.pricing {
        padding: 22px 18px;
    }
    
    .price-number {
        font-size: 38px;
    }
    
    .price-title {
        font-size: 21px;
    }
    
    .price-features {
        margin: 18px 0;
    }
    
    .feature-item {
        padding: 9px 0;
        gap: 10px;
    }
    
    .feature-icon {
        width: 30px;
        height: 30px;
        font-size: 13px;
    }
    
    .feature-text {
        font-size: 13px;
    }
    
    .price-btn {
        padding: 14px 20px;
        font-size: 14px;
        min-height: 48px;
    }
    
    .price-note {
        font-size: 12.5px;
    }
}

    @media(max-width: 768px) {
        .steps {
            flex-direction: column;
            align-items: center;
            gap: 30px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .step {
            width: 100%;
            max-width: 350px;
            text-align: center;
            padding: 25px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            margin: 0 auto 15px;
        }
        
        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .step p {
            font-size: 0.95rem;
            line-height: 1.5;
        }
    }

    @media(max-width: 480px) {
        .step {
            padding: 20px 15px;
        }
        
        .step-number {
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
        }
        
        .step h3 {
            font-size: 1.2rem;
        }
        
        .step p {
            font-size: 0.9rem;
        }
    }

    @media(max-width: 900px) {
        .card.pricing {
            padding: 30px 20px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .price-number {
            font-size: 44px;
        }
        
        .price-title {
            font-size: 24px;
        }
        
        .price-text {
            font-size: 15px;
        }
        
        .feature-item {
            padding: 10px 0;
        }
        
        .feature-icon {
            width: 28px;
            height: 28px;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .feature-text {
            font-size: 14px;
        }
        
        .price-btn {
            padding: 14px 25px;
            font-size: 15px;
        }
    }

    @media(max-width: 768px) {
        .card.pricing {
            padding: 25px 18px;
        }
        
        .price-number {
            font-size: 40px;
            margin: 15px 0;
        }
        
        .price-features {
            margin: 20px 0;
        }
        
        .price-action {
            margin-top: 25px;
        }
        
        .price-note {
            font-size: 13px;
        }
    }

    @media(max-width: 480px) {
        .card.pricing {
            padding: 20px 15px;
        }
        
        .price-number {
            font-size: 36px;
        }
        
        .price-title {
            font-size: 22px;
        }
        
        .feature-item {
            padding: 8px 0;
            gap: 10px;
        }
        
        .feature-icon {
            width: 24px;
            height: 24px;
            font-size: 13px;
        }
        
        .feature-text {
            font-size: 13px;
        }
        
        .price-btn {
            padding: 13px 20px;
            font-size: 14px;
        }
        
        .btn-icon {
            font-size: 16px;
        }
    }

    .nav-section {
        margin-bottom: 25px;
    }

    .nav-section-label {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
        padding-right: 15px;
        font-weight: 600;
    }

    .mobile-nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 15px;
        margin-bottom: 8px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .mobile-nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(138, 108, 255, 0.3);
        transform: translateX(-5px);
    }

    .mobile-nav-link.highlight {
        background: linear-gradient(135deg, rgba(138, 108, 255, 0.15), rgba(124, 124, 255, 0.1));
        border-color: rgba(138, 108, 255, 0.3);
    }

    .mobile-nav-link.highlight:hover {
        background: linear-gradient(135deg, rgba(138, 108, 255, 0.25), rgba(124, 124, 255, 0.2));
    }

    .mobile-nav-link.admin {
        border-left: 3px solid #ff6b6b;
    }

    .mobile-nav-link.logout {
        border-left: 3px solid #e74c3c;
    }

    .link-content {
        display: flex;
        align-items: center;
        gap: 15px;
        flex: 1;
    }

    .link-icon {
        font-size: 18px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .link-text {
        color: white;
        font-weight: 500;
        font-size: 15px;
    }

    .link-arrow {
        color: rgba(255, 255, 255, 0.4);
        font-size: 18px;
        font-weight: bold;
    }

    .link-badge {
        background: rgba(138, 108, 255, 0.2);
        color: var(--accent);
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: bold;
        border: 1px solid rgba(138, 108, 255, 0.3);
    }

    .link-badge.admin-badge {
        background: rgba(255, 107, 107, 0.2);
        color: #ff6b6b;
        border-color: rgba(255, 107, 107, 0.3);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .mobile-nav.show .mobile-nav-link {
        animation: slideIn 0.3s ease forwards;
    }

    .mobile-nav.show .mobile-nav-link:nth-child(1) { animation-delay: 0.1s; }
    .mobile-nav.show .mobile-nav-link:nth-child(2) { animation-delay: 0.15s; }
    .mobile-nav.show .mobile-nav-link:nth-child(3) { animation-delay: 0.2s; }
    .mobile-nav.show .mobile-nav-link:nth-child(4) { animation-delay: 0.25s; }
    .mobile-nav.show .mobile-nav-link:nth-child(5) { animation-delay: 0.3s; }
    .mobile-nav.show .mobile-nav-link:nth-child(6) { animation-delay: 0.35s; }
    .mobile-nav.show .mobile-nav-link:nth-child(7) { animation-delay: 0.4s; }
    .mobile-nav.show .mobile-nav-link:nth-child(8) { animation-delay: 0.45s; }
    .mobile-nav.show .mobile-nav-link:nth-child(9) { animation-delay: 0.5s; }

    .mobile-nav-link.active {
        background: rgba(138, 108, 255, 0.15);
        border-color: rgba(138, 108, 255, 0.4);
    }

    .mobile-nav-link.active .link-icon {
        background: rgba(138, 108, 255, 0.3);
    }

    .mobile-nav-link.active .link-text {
        color: var(--accent);
        font-weight: 600;
    }

    @media (prefers-color-scheme: dark) {
        .nav-section-label {
            color: rgba(255, 255, 255, 0.6);
        }
    }

    @media (max-width: 480px) {
        .mobile-nav-link {
            padding: 12px 15px;
        }
        
        .link-icon {
            width: 28px;
            height: 28px;
            font-size: 16px;
        }
        
        .link-text {
            font-size: 14px;
        }
        
        .link-badge {
            font-size: 10px;
            padding: 3px 8px;
        }
    }

    @media (hover: none) and (pointer: coarse) {
        .mobile-nav-link:active {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(0.98);
        }
        
        .mobile-nav-link.highlight:active {
            background: linear-gradient(135deg, rgba(138, 108, 255, 0.25), rgba(124, 124, 255, 0.2));
        }
    }
</style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-header">
        <div class="logo">
            <div class="logo-icon">
                <img src="assets/images/icon.png" alt="Legion Upload Logo">
            </div>
            <span>Legion Upload</span>
        </div>
    </div>
    
    <div class="mobile-nav-links">
        <div class="nav-section">
            <div class="nav-section-label">منو اصلی</div>
            <a href="#features" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">✨</span>
                    <span class="link-text">ویژگی‌ها</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
            <a href="#how" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">🔄</span>
                    <span class="link-text">نحوه کار</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
            <a href="#pricing" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">💰</span>
                    <span class="link-text">قیمت‌گذاری</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
            <a href="#download" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">📥</span>
                    <span class="link-text">دانلود فایل</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
            <a href="#faq" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">❓</span>
                    <span class="link-text">سوالات متداول</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-label">عملیات</div>
            <a href="upload.php" class="mobile-nav-link highlight" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">🚀</span>
                    <span class="link-text">آپلود فایل جدید</span>
                </div>
                <span class="link-badge">جدید</span>
            </a>
        </div>
        
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="nav-section">
            <div class="nav-section-label">حساب کاربری</div>
            <a href="dashboard.php" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">📊</span>
                    <span class="link-text">داشبورد</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
            <a href="myfiles.php" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">📁</span>
                    <span class="link-text">فایل‌های من</span>
                </div>
                <span class="link-badge" id="fileCount">0</span>
            </a>
            <?php if($is_admin): ?>
            <a href="admin.php" class="mobile-nav-link admin" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">⚙️</span>
                    <span class="link-text">پنل ادمین</span>
                </div>
                <span class="link-badge admin-badge">ادمین</span>
            </a>
            <?php endif; ?>
            <a href="logout.php" class="mobile-nav-link logout" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">🚪</span>
                    <span class="link-text">خروج از حساب</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
        </div>
        <?php else: ?>
        <div class="nav-section">
            <div class="nav-section-label">ورود / ثبت نام</div>
            <a href="login.php" class="mobile-nav-link" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">🔐</span>
                    <span class="link-text">ورود به حساب</span>
                </div>
                <span class="link-arrow">›</span>
            </a>
            <a href="register.php" class="mobile-nav-link highlight" onclick="closeMobileMenu()">
                <div class="link-content">
                    <span class="link-icon">📝</span>
                    <span class="link-text">ثبت نام جدید</span>
                </div>
                <span class="link-badge">رایگان</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div class="page">
  <header class="navbar">
    <div class="logo">
        <div class="logo-icon">
          <img src="assets/images/icon.png" alt="Legion Upload Logo">
        </div>
      <span id="siteName">Legion Upload</span>
    </div>

    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav class="nav-right">
      <a href="#features" id="navFeatures">ویژگی‌ها</a>
      <a href="#how" id="navHow">نحوه کار</a>
      <a href="#pricing" id="navPricing">قیمت</a>
      <a href="#download" id="navDownload">دانلود فایل</a>
      <a href="#faq" id="navFaq">سوالات</a>
      <a class="nav-btn" href="upload.php" id="navUpload">آپلود</a>
      
      <?php if(isset($_SESSION['user_id'])): ?>
        <div class="profile-menu">
          <button class="profile-btn" onclick="toggleProfileMenu()">
            👤 <?php echo htmlspecialchars($username); ?>
          </button>
          <div class="profile-popup" id="profilePopup">
            <div class="popup-header">حساب کاربری</div>
            <div class="popup-item" onclick="window.location.href='dashboard.php'">داشبورد</div>
            <div class="popup-item" onclick="window.location.href='myfiles.php'">فایل‌های من</div>
            <?php if($is_admin): ?>
            <div class="popup-item" onclick="window.location.href='admin.php'">پنل ادمین</div>
            <?php endif; ?>
            <div class="popup-item logout" onclick="window.location.href='logout.php'">خروج</div>
          </div>
        </div>
      <?php else: ?>
        <a class="nav-btn" href="login.php" id="navLogin">ورود</a>
      <?php endif; ?>
      
      <button class="lang-btn" id="langBtn">English</button>
    </nav>
  </header>

  <section class="hero">
    <div class="hero-left">
      <h1 id="heroTitle">فایل‌ها را امن ارسال کنید</h1>
      <p id="heroText">آپلود کنید، تایمر یا پسورد بگذارید، کد را ارسال کنید.</p>
      <div class="hero-buttons">
        <a class="btn" href="upload.php" id="btnUpload">آپلود فایل</a>
        <a class="btn-outline" href="#download" id="btnDownload">دانلود فایل</a>
      </div>

      <div class="hero-stats">
        <div class="stat">
          <div class="stat-num">1GB</div>
          <div class="stat-text" id="stat1">حداکثر آپلود</div>
        </div>
        <div class="stat">
          <div class="stat-num">24h</div>
          <div class="stat-text" id="stat2">حداکثر تایمر</div>
        </div>
        <div class="stat">
          <div class="stat-num">Code</div>
          <div class="stat-text" id="stat3">کد اشتراک</div>
        </div>
      </div>
    </div>

    <div class="hero-right">
      <div class="mockup">
        <div class="mockup-top">
          <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 10px;">
          </div>
        </div>
        <div class="mockup-content">
          <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 50px; height: 50px; background: rgba(138,108,255,0.3); border-radius: 15px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-size: 24px;">📁</div>
            <div style="color: white; font-weight: bold; font-size: 16px;" id="s1">فایل آماده دانلود</div>
            <div style="color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 5px;" id="s2">کد: 1234567</div>
          </div>
          
          <div class="mockup-line" style="background: rgba(138,108,255,0.2);"></div>
          <div class="mockup-line short" style="background: rgba(255,255,255,0.1);"></div>
          
          <div style="padding: 15px; background: rgba(138,108,255,0.1); border-radius: 10px; margin: 15px 0; text-align: center;">
            <div style="color: rgba(255,255,255,0.9); font-size: 14px;" id="s3">برای دانلود کد را وارد کنید</div>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
              <div style="flex: 1; height: 40px; background: rgba(255,255,255,0.1); border-radius: 8px;"></div>
              <div style="width: 80px; height: 40px; background: var(--accent); border-radius: 8px;"></div>
            </div>
          </div>
          
          <div class="mockup-line" style="background: rgba(255,255,255,0.1);"></div>
          <div class="mockup-line short" style="background: rgba(138,108,255,0.2);"></div>
        </div>
        <div class="mockup-bottom">
          <div style="width: 40px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; margin: 0 auto;"></div>
        </div>
      </div>
      <div class="glow"></div>
    </div>
  </section>

  <section id="download" class="section">
    <div class="section-title">
        <h2 id="d1">دانلود فایل</h2>
        <p id="d2">کد دریافت شده را وارد کنید و فایل را دانلود نمایید</p>
    </div>
    
    <div class="download-section">
        <h3 style="margin-bottom: 20px;" id="d3">کد فایل را وارد کنید</h3>
        <form id="downloadForm" class="download-form">
            <input type="text" id="fileCode" class="download-input" placeholder="مثال: 1234567" required>
            <button type="submit" class="download-btn">
                <span>🔍</span>
                <span id="d4">مشاهده فایل</span>
            </button>
        </form>
        <div id="downloadResult" style="margin-top: 30px;"></div>
    </div>
</section>

  <section id="features" class="section">
    <div class="section-title">
      <h2 id="featuresTitle">ویژگی‌ها</h2>
      <p id="featuresText">همه چیز برای اشتراک گذاری امن فایل</p>
    </div>

    <div class="grid">
      <div class="card">
        <div class="icon">🔒</div>
        <h3 id="f1">پسورد اختیاری</h3>
        <p id="f1text">فقط اگر نیاز دارید فعال کنید.</p>
      </div>
      <div class="card">
        <div class="icon">⏱️</div>
        <h3 id="f2">تایمر حذف</h3>
        <p id="f2text">1h / 3h / 12h / 24h.</p>
      </div>
      <div class="card">
        <div class="icon">🔗</div>
        <h3 id="f3">اشتراک با کد</h3>
        <p id="f3text">کد را به گیرنده بدهید.</p>
      </div>
      <div class="card">
        <div class="icon">🗃️</div>
        <h3 id="f4">داشبورد</h3>
        <p id="f4text">تمام فایل‌های آپلودی خود را ببینید.</p>
      </div>
      <div class="card">
        <div class="icon">📦</div>
        <h3 id="f5">1GB محدودیت</h3>
        <p id="f5text">برای هر حساب کاربری.</p>
      </div>
      <div class="card">
        <div class="icon">🧼</div>
        <h3 id="f6">UI شیشه‌ای</h3>
        <p id="f6text">ظاهر مدرن و زیبا.</p>
      </div>
    </div>
  </section>

  <section id="how" class="section section-dark">
    <div class="section-title">
      <h2 id="howTitle">نحوه کار</h2>
      <p id="howText">3 مرحله ساده</p>
    </div>

    <div class="steps">
      <div class="step">
        <div class="step-number">1</div>
        <h3 id="step1">آپلود فایل</h3>
        <p id="step1text">فایل را انتخاب و تایمر/پسورد را تنظیم کنید.</p>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <h3 id="step2">کد دریافت کنید</h3>
        <p id="step2text">کد تولید شده را کپی کنید.</p>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <h3 id="step3">اشتراک و دانلود</h3>
        <p id="step3text">کد را بدهید و فایل دانلود شود.</p>
      </div>
    </div>
  </section>

  <section id="pricing" class="section">
    <div class="section-title">
      <h2 id="pricingTitle">قیمت</h2>
      <p id="pricingText">پلن‌های ما</p>
    </div>

    <div class="grid">
      <div class="card pricing">
        <div class="price-title" id="priceTitle">رایگان</div>
        <div class="price-number">1GB</div>
        <div class="price-text" id="priceText">حداکثر آپلود</div>
        
        <div class="price-features">
          <div class="feature-item">
            <span class="feature-icon">🔒</span>
            <span class="feature-text" id="fe1">پسورد محافظت شده</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">⏱️</span>
            <span class="feature-text" id="fe2">تایمر خودکار حذف</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">🔗</span>
            <span class="feature-text" id="fe3">اشتراک با کد یکتا یا لینک</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">📊</span>
            <span class="feature-text" id="fe4">داشبورد پیشرفته</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">⚡</span>
            <span class="feature-text" id="fe5">سرعت دانلود بالا</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">🛡️</span>
            <span class="feature-text" id="fe6">امنیت بالا</span>
          </div>
        </div>
        
        <div class="price-action">
          <a class="btn price-btn" href="register.php" id="priceBtn">
            <span>ثبت نام رایگان</span>
            <span class="btn-icon">🎯</span>
          </a>
          <p class="price-note" id="fe7">بدون نیاز به کارت اعتباری</p>
        </div>
      </div>

      <div class="card pricing">
        <div class="price-title" id="priceTitlePremium">پیشرفته</div>
        <div class="price-number">20GB</div>
        <div class="price-text" id="priceTextPremium">حداکثر آپلود</div>
        
        <div class="price-features">
          <div class="feature-item">
            <span class="feature-icon">🔒</span>
            <span class="feature-text" id="fe1P">پسورد محافظت شده</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">⏱️</span>
            <span class="feature-text" id="fe2P">تایمر تا ۷ روز</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">🔗</span>
            <span class="feature-text" id="fe3P">اشتراک با کد یکتا یا لینک</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">⚡</span>
            <span class="feature-text" id="fe5P">اولویت سرعت</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">🛡️</span>
            <span class="feature-text" id="fe6P">رمزگذاری پیشرفته</span>
          </div>
        </div>
        
        <div class="price-action">
          <a class="btn price-btn" href="#" id="priceBtnPremium">
            <span>خرید پلن</span>
            <span class="btn-icon">⭐</span>
          </a>
          <p class="price-note" id="fe7P">25,000 تومان ماهانه</p>
        </div>
      </div>
    </div>
  </section>

  <section id="faq" class="section section-dark">
    <div class="section-title">
      <h2 id="faqTitle">سوالات متداول</h2>
      <p id="faqText">پاسخ به سوالات رایج</p>
    </div>

    <div class="faq">
      <div class="faq-item">
        <h3 id="q1">تایمر چگونه کار می‌کند؟</h3>
        <p id="a1">فایل بعد از زمان انتخاب شده حذف می‌شود.</p>
      </div>
      <div class="faq-item">
        <h3 id="q2">بدون حساب می‌توان آپلود کرد؟</h3>
        <p id="a2">خیر، برای آپلود باید وارد شوید.</p>
      </div>
      <div class="faq-item">
        <h3 id="q3">اگر فایل بیشتر از 1GB باشد؟</h3>
        <p id="a3">آپلود انجام نمی‌شود، باید کمتر از 1GB باشد.</p>
      </div>
    </div>
  </section>

  <footer class="footer">
    <p id="footerText">© 2026 Legion Upload — ساخته شده با ❤ TheWindows</p>
  </footer>

</div>

<div class="code-popup" id="codePopup">
  <button class="close-btn" onclick="hideCodePopup()">×</button>
  <h3>کد جدید ایجاد شد</h3>
  <div class="code-display" id="generatedCode"></div>
  <p>این کد را برای اشتراک فایل استفاده کنید</p>
  <button class="btn" onclick="copyCode()">کپی کد</button>
  <button class="btn-outline" onclick="hideCodePopup()">بستن</button>
</div>

<script src="assets/js/app.js"></script>
<script>
    let profileOpen = false;
    let codePopupOpen = false;
    let mobileMenuOpen = false;
    
    function toggleProfileMenu() {
        const popup = document.getElementById('profilePopup');
        if (!popup) return;
        
        profileOpen = !profileOpen;
        popup.classList.toggle('show', profileOpen);
        
        if (profileOpen) {
            setTimeout(() => {
                document.addEventListener('click', closeProfileOnClickOutside);
            }, 10);
        } else {
            document.removeEventListener('click', closeProfileOnClickOutside);
        }
    }
    
    function closeProfileOnClickOutside(event) {
        const popup = document.getElementById('profilePopup');
        const btn = document.querySelector('.profile-btn');
        
        if (popup && btn && !popup.contains(event.target) && !btn.contains(event.target)) {
            popup.classList.remove('show');
            profileOpen = false;
            document.removeEventListener('click', closeProfileOnClickOutside);
        }
    }
    
    function showCodePopup() {
        const codePopup = document.getElementById('codePopup');
        const overlay = document.getElementById('overlay');
        const codeDisplay = document.getElementById('generatedCode');
        
        if (!codePopup || !overlay || !codeDisplay) return;
        
        const code = Math.floor(1000000 + Math.random() * 9000000);
        codeDisplay.textContent = code;
        
        codePopup.classList.add('show');
        overlay.classList.add('show');
        codePopupOpen = true;
        
        if (document.getElementById('profilePopup')) {
            document.getElementById('profilePopup').classList.remove('show');
            profileOpen = false;
        }
        
        if (mobileMenuOpen) {
            closeMobileMenu();
        }
    }
    
    function hideCodePopup() {
        const codePopup = document.getElementById('codePopup');
        const overlay = document.getElementById('overlay');
        
        if (!codePopup || !overlay) return;
        
        codePopup.classList.remove('show');
        overlay.classList.remove('show');
        codePopupOpen = false;
    }
    
    function copyCode() {
        const codeDisplay = document.getElementById('generatedCode');
        if (!codeDisplay) return;
        
        const code = codeDisplay.textContent;
        navigator.clipboard.writeText(code).then(() => {
            alert('کد کپی شد: ' + code);
        });
    }
    
    function toggleMobileMenu() {
        const mobileNav = document.getElementById('mobileNav');
        const mobileOverlay = document.getElementById('mobileNavOverlay');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        
        if (!mobileNav || !mobileOverlay || !mobileBtn) return;
        
        mobileMenuOpen = !mobileMenuOpen;
        
        mobileNav.classList.toggle('show', mobileMenuOpen);
        mobileOverlay.classList.toggle('show', mobileMenuOpen);
        mobileBtn.classList.toggle('active', mobileMenuOpen);
        
        if (profileOpen && document.getElementById('profilePopup')) {
            document.getElementById('profilePopup').classList.remove('show');
            profileOpen = false;
        }
        
        if (codePopupOpen) {
            hideCodePopup();
        }
    }
    
    function closeMobileMenu() {
        const mobileNav = document.getElementById('mobileNav');
        const mobileOverlay = document.getElementById('mobileNavOverlay');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        
        if (!mobileNav || !mobileOverlay || !mobileBtn) return;
        
        mobileMenuOpen = false;
        mobileNav.classList.remove('show');
        mobileOverlay.classList.remove('show');
        mobileBtn.classList.remove('active');
    }
    
    document.getElementById('mobileMenuBtn').addEventListener('click', toggleMobileMenu);
    document.getElementById('mobileNavOverlay').addEventListener('click', closeMobileMenu);
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideCodePopup();
            if (profileOpen) {
                document.getElementById('profilePopup').classList.remove('show');
                profileOpen = false;
            }
            if (mobileMenuOpen) {
                closeMobileMenu();
            }
        }
    });
    
    document.getElementById('overlay').addEventListener('click', function() {
        hideCodePopup();
    });
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                if (mobileMenuOpen) {
                    closeMobileMenu();
                }
                
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });
</script>
</body>
</html>