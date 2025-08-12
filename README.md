<div align="center">

# 🔐 Password Manager

*Secure • Share • Control*

[![Live Demo](https://img.shields.io/badge/🌐_Live_Demo-kritarth.byethost14.com-blue?style=for-the-badge)](http://kritarth.byethost14.com/password_manager/)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)]()
[![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)]()
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)]()

**Manage your passwords with style. Share securely with time limits. Control everything.**

[🚀 Try Live Demo](http://kritarth.byethost14.com/password_manager/) • [📖 Features](#-features) • [⚡ Quick Start](#-quick-start)

</div>

---

## ✨ What Makes This Special?

> **"Finally, a password manager that doesn't treat sharing like a security nightmare."**

🎯 **Smart & Secure** - Your passwords are encrypted and protected  
⏰ **Time-Limited Sharing** - Share passwords that expire automatically  
🔄 **Live Updates** - Shared passwords update in real-time when you change them  
🎨 **Beautiful UI** - Clean, modern interface you'll actually enjoy using

---

## 🌟 Features

### 🔒 **Account Management**
- **Secure Login/Signup** - Strong authentication system
- **Smart Password Recovery** - PIN or personal questions
- **Session Management** - Auto-logout for security

### 💾 **Password Storage**
- **Organized Categories** - Personal, Banking, Work, Social, etc.
- **Search & Filter** - Find passwords instantly
- **Import Support** - Chrome, Firefox, Brave exports
- **Secure Encryption** - Your data stays private

### 🔗 **Smart Sharing** 
- **Time Limits** - 30min, 1hr, 1day, 1week, 1month
- **Live Updates** - Shared passwords sync automatically
- **Easy Revoke** - Cancel sharing anytime
- **Email Notifications** - Optional email sharing

### 🎨 **User Experience**
- **Clean Interface** - Minimalist, intuitive design
- **Password Strength** - Visual strength indicators  
- **Responsive Design** - Works on all devices
- **Quick Actions** - Copy, view, edit with one click

---

## 🎮 Live Demo

**Try it yourself:** [kritarth.byethost14.com/password_manager](http://kritarth.byethost14.com/password_manager/)

### Demo Features:
- ✅ Create test account
- ✅ Add/edit passwords  
- ✅ Share with time limits
- ✅ Import browser passwords
- ✅ All features working

---

## ⚡ Quick Start

### 🛠️ Setup (5 minutes)

```bash
# 1. Clone the repo
git clone https://github.com/Kritarth123-prince/Password-Manager.git

# 2. Navigate to folder
cd Password-Manager

# 3. Import database
mysql -u root -p < database.sql
```

### 🔧 Configure

Edit `db_config.php`:
```php
$db = new mysqli('localhost', 'username', 'password', 'database_name');

// Email settings (optional)
$email_sender = 'your-email@gmail.com';
$email_password = 'app-password';
```

### 🚀 Deploy
- Upload files to your server
- Set proper file permissions
- Update database credentials
- You're ready! 🎉

---

## 🏗️ Architecture

```
📁 Password Manager
├── 🔐 login.php          → Authentication
├── 📝 signup.php         → User registration  
├── 🔄 forgot.php         → Password recovery
├── 👀 view.php           → Dashboard & search
├── ➕ save.php           → Add/import passwords
├── ✏️ edit.php           → Edit existing entries
├── 🔗 share.php          → View shared passwords
├── ⚙️ db_config.php      → Database config
└── 🚪 logout.php         → Session cleanup
```

---

## 💡 How Sharing Works

### Create Share Link
1. Click share button 🔗
2. Set expiration time ⏰  
3. Add optional email 📧
4. Get shareable link 🔗

### Magic Features
- **Auto-Expire** - Links die after time limit
- **Live Sync** - Updates reflect instantly
- **Revoke Control** - Cancel anytime
- **No Account Needed** - Recipients just click link

---

## 🛡️ Security Features

| Feature | Description |
|---------|-------------|
| 🔐 **Password Hashing** | bcrypt encryption |
| 🔒 **SQL Injection Protection** | Prepared statements |
| ⏰ **Session Timeout** | Auto-logout inactive users |
| 🚫 **XSS Protection** | Input sanitization |
| 🔑 **Secure Sharing** | Time-limited tokens |
| 💾 **Safe Storage** | Encrypted sensitive data |

---

## 🎨 Screenshots

<div align="center">

### 🏠 Dashboard
![Dashboard](https://via.placeholder.com/600x300/667eea/ffffff?text=Clean+Dashboard+View)

### 🔗 Smart Sharing  
![Sharing](https://via.placeholder.com/600x300/764ba2/ffffff?text=Time-Limited+Password+Sharing)

### 📱 Mobile Responsive
![Mobile](https://via.placeholder.com/300x500/667eea/ffffff?text=Mobile+Optimized)

</div>

---

## 🎯 Use Cases

### 👨‍💼 **For Teams**
- Share project credentials temporarily
- Revoke access when team members leave
- Track who has access to what

### 👨‍👩‍👧‍👦 **For Families** 
- Share Netflix, WiFi passwords safely
- Emergency access to important accounts
- Time-limited sharing for guests

### 🏢 **For Businesses**
- Secure client credential sharing
- Temporary contractor access
- Audit trail for shared passwords

---

## 🤝 Contributing

We love contributions! Here's how to help:

1. **🍴 Fork** the repository
2. **🌿 Create** your feature branch
3. **💾 Commit** your changes  
4. **🚀 Push** to the branch
5. **📝 Open** a Pull Request

### 💡 Ideas Welcome
- New sharing options
- Better mobile experience  
- Additional import formats
- Security improvements

---

## 📝 License

This project is **open source** and available under the [MIT License](LICENSE).

---

## 🙋‍♂️ Support

Having issues? We're here to help!

- 🐛 **Bug Reports** - Open an issue
- 💡 **Feature Requests** - Let us know what you need
- 🤝 **General Help** - Check existing issues first

---

<div align="center">

### 🌟 **Like this project?** 

**Give it a star ⭐ and share with friends!**

[⭐ Star this repo](../../) • [🔗 Share on Twitter](https://twitter.com/intent/tweet?text=Check%20out%20this%20awesome%20Password%20Manager%21&url=https://github.com/Kritarth123-prince/Password-Manager) • [💬 Join Discussion](../../discussions)

---

**Made with ❤️ for secure password management**

*Manage • Share • Control - All in one place* 🔐

</div>
