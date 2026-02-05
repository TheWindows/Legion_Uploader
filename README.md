# Legion Uploader (Beta) 📁

<div align="center">

**A modern, secure file uploader with glassmorphism UI and admin panel**

⚠️ **BETA SOFTWARE** - Use with caution! May contain bugs and incomplete features.

</div>

## 📋 Table of Contents
- [⚠️ Beta Notice](#️-beta-notice)
- [✨ Features](#-features)
- [🛠️ Tech Stack](#️-tech-stack)
- [🚀 Quick Start](#-quick-start)
- [⚙️ Server Configuration](#️-server-configuration)
- [⚠️ Database Import Alert](#️-database-import-alert)
- [📁 Project Structure](#-project-structure)
- [🔧 TODO & Development](#-todo--development)
- [🛡️ Security](#️-security)
- [🐛 Known Issues](#-known-issues)
- [🤝 Contributing](#-contributing)
- [📞 Support](#-support)
- [📄 License](#-license)

## ⚠️ Beta Notice

<div align="center">
  
**🚨 HEADS UP - THIS IS BETA SOFTWARE 🚨**

</div>

Alright, let's be real here - this isn't finished yet. Legion Uploader is still in the oven, and while it's got some cool features, it might have a few rough edges.

### What you're getting into:
- 🐛 **Stuff might break** - and not in a cool "I can fix it" way
- 📧 **Email system is wonky** - sometimes it sends, sometimes it doesn't
- 🔧 **Features are half-baked** - some things look done but aren't
- ⚡ **Unexpected surprises** - the fun kind (errors) and the not-so-fun kind

### How to not get frustrated:
- 👍 **Test it like you're trying to break it** - because you probably will
- 💾 **Backup everything** - your database, your files, your sanity
- 🧪 **Use it in a safe space** - not on your production server
- 📝 **Tell us when it breaks** - we can't fix what we don't know about
- 😅 **Bring patience** - this is a work in progress

---

## ✨ Features

### 🎨 Looks That Don't Suck
- **Frosty glass effects** that make it look modern
- **Works on your phone** and your computer
- **Smooth animations** - no janky transitions
- **Dark mode** for late-night uploading sessions
- **Clean layout** - you won't get lost (probably)

### 🔒 Actually Secure Stuff
- **Passwords are hashed** - not stored in plain text (thank goodness)
- **SQL injection protection** - because hackers are no fun
- **XSS prevention** - keeps the bad scripts out
- **Session security** - your login stays yours
- **File validation** - no uploading viruses, please

### 👨‍💼 Admin Superpowers
- **Manage users** - add, edit, or remove people
- **File oversight** - see what everyone's uploading
- **Upload stats** - charts and graphs for data nerds
- **System settings** - tweak how things work
- **Activity logs** - see who did what and when

### 📁 Uploading That Works (Mostly)
- **Upload multiple files** - because one at a time is boring
- **Smart limitations** - keeps people from uploading their entire movie collection
- **Progress bars** - watch the little bar fill up
- **Organized files** - sorted by user, date, and type
- **File previews** - see images and documents before downloading

### 🗄️ Database That Holds Your Stuff
- **MySQL powered** - reliable data storage
- **Smart structure** - organized and efficient
- **Backup ready** - export your data easily
- **Fast queries** - snappy performance

---

## 🛠️ Tech Stack

| Technology | What It Does |
|------------|-------------|
| **PHP** | The brain - handles all the logic and processing |
| **MySQL** | The memory - stores all your data and files info |
| **JavaScript** | The personality - makes things interactive and fun |
| **HTML5** | The skeleton - structures everything on the page |
| **CSS3** | The wardrobe - makes it all look pretty |
| **Apache** | The doorman - serves everything to your browser |

---

## 🚀 Quick Start

### What You Need First

1. **Pick Your Server Software:**
   - [WAMP Server](https://www.wampserver.com/en/) (Windows folks)
   - [XAMPP](https://www.apachefriends.org/) (Works everywhere)
   - [Laragon](https://laragon.org/) (Windows, but fancy)

2. **Your Computer Needs:**
   - PHP 8.0 or newer
   - MySQL 5.7 or newer
   - Apache 2.4 or newer
   - About 100MB of free space

---

## ⚙️ Server Configuration

<div align="center" style="background-color: #e7f3ff; border: 2px solid #4da6ff; border-radius: 10px; padding: 20px; margin: 20px 0;">

### ⚡ **TUNE UP YOUR PHP FOR BIG FILES** ⚡

**You need to adjust these settings or big files won't upload!**

</div>

### The .htaccess File
Don't worry about this one too much - it's already included in the project. It does these important things:
- **Allows big file uploads** (up to 1GB files!)
- **Gives PHP more time** to process large uploads
- **Adds security headers** to protect against attacks
- **Hides sensitive files** from prying eyes
- **Sets up clean URLs** (no ugly `index.php?page=home`)

### Manual PHP.ini Tweaks (If You Need Them)

Sometimes the .htaccess file doesn't work perfectly. If you're having trouble with big files:

#### For XAMPP Users:
1. Find `php.ini` in: `C:\xampp\php\php.ini`
2. Open it with Notepad (or any text editor)
3. Find and change these lines:
   ```ini
   upload_max_filesize = 1024M
   post_max_size = 1024M
   max_execution_time = 300
   max_input_time = 300
   memory_limit = 256M
