# Legion Uploader (Beta) 📁

<div align="center">

![Beta Version](https://img.shields.io/badge/Version-Beta-orange?style=for-the-badge&logo=beta)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**A modern, secure file uploader with glassmorphism UI and admin panel**

⚠️ **BETA SOFTWARE** - Use with caution! May contain bugs and incomplete features.

</div>

## 📋 Table of Contents
- [⚠️ Beta Notice](#️-beta-notice)
- [✨ Features](#-features)
- [🛠️ Tech Stack](#️-tech-stack)
- [🚀 Quick Start](#-quick-start)
- [⚠️ Database Import Alert](#️-database-import-alert)
- [⚙️ Configuration](#️-configuration)
- [📁 Project Structure](#-project-structure)
- [🔧 TODO & Development](#-todo--development)
- [🛡️ Security](#️-security)
- [🐛 Known Issues](#-known-issues)
- [🤝 Contributing](#-contributing)
- [📞 Support](#-support)
- [📄 License](#-license)

## ⚠️ Beta Notice

<div align="center">
  
**🚨 IMPORTANT - READ BEFORE USING 🚨**

</div>

This is **BETA software** and is under active development. 

### What this means:
- 🔴 **Features may break** unexpectedly
- 🔴 **Bugs are expected** and may cause data loss
- 🔴 **Security vulnerabilities** may exist
- 🔴 **APIs may change** without warning
- 🔴 **Not production-ready**

### Recommendations:
- ✅ **Test thoroughly** before any real use
- ✅ **Backup regularly** - especially your database
- ✅ **Use in isolated environments** only
- ✅ **Report all issues** you encounter
- ✅ **Expect instability** during use

---

## ✨ Features

### 🎨 Modern UI
- **Glassy Glassmorphism** design with frosted glass effects
- **Responsive layout** that works on desktop and mobile
- **Smooth animations** and transitions
- **Dark/Light mode** support
- **Clean, intuitive interface**

### 🔐 Security
- **Hashed passwords** using `password_hash()` with bcrypt
- **SQL Injection protection** via prepared statements
- **XSS prevention** with output encoding
- **Session security** with regeneration
- **File validation** (MIME types, extensions, size)

### 👨‍💼 Admin Panel
- **User management** (add/edit/delete users)
- **File management** system
- **Upload statistics** and analytics
- **System configuration** interface
- **Activity logs** and monitoring

### 📁 Upload System
- **Multiple file uploads** support
- **Upload limitations** (size, type, quantity)
- **Progress indicators** with real-time feedback
- **File organization** by user/date/category
- **Preview capabilities** for images and documents

### 🗄️ Database
- **MySQL integration** with PDO
- **Efficient schema** design
- **Backup and restore** functionality
- **Query optimization** for performance

---

## 🛠️ Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **PHP** | 8.0+ | Backend logic and server-side processing |
| **MySQL** | 5.7+ | Database storage and management |
| **JavaScript** | ES6+ | Frontend interactivity and AJAX |
| **HTML5** | Latest | Markup and structure |
| **CSS3** | Latest | Styling with glassmorphism effects |
| **Apache** | 2.4+ | Web server (via WAMP/XAMPP) |

---

## 🚀 Quick Start

### Prerequisites

1. **Install one of these:**
   - [WAMP Server](https://www.wampserver.com/en/) (Windows)
   - [XAMPP](https://www.apachefriends.org/) (Cross-platform)
   - [Laragon](https://laragon.org/) (Windows)

2. **System Requirements:**
   - PHP 8.0 or higher
   - MySQL 5.7 or higher
   - Apache 2.4 or higher
   - 100MB free disk space

### Installation Steps

#### Step 1: Download and Extract
```bash
# Clone or download the repository
git clone https://github.com/yourusername/legion-uploader.git
# OR download ZIP and extract to server directory
