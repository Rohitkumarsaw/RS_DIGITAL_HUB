<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=2563EB&height=200&section=header&text=RS%20Digital%20Hub&fontSize=60&fontAlignY=35&animation=fadeIn&desc=Premium%20Digital%20Product%20Marketplace&descAlignY=55"/>
</p>

<p align="center">
  <a href="#-features"><img src="https://img.shields.io/badge/Features-🚀-blue?style=flat-square"/></a>
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/Tech-⚙️-purple?style=flat-square"/></a>
  <a href="#-quick-start"><img src="https://img.shields.io/badge/Install-📦-green?style=flat-square"/></a>
  <a href="#-screenshots"><img src="https://img.shields.io/badge/Preview-🖼️-orange?style=flat-square"/></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/Razorpay-Payments-02042B?style=for-the-badge&logo=razorpay&logoColor=white"/>
  <img src="https://img.shields.io/badge/TCPDF-Documents-FF6B6B?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black"/>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Status-Production%20Ready-22c55e?style=flat-square"/>
  <img src="https://img.shields.io/badge/Security-Audited-6366f1?style=flat-square"/>
  <img src="https://img.shields.io/badge/Maintained-Yes-22c55e?style=flat-square"/>
  <img src="https://img.shields.io/badge/PRs-Welcome-22c55e?style=flat-square"/>
</p>

---

## 📋 Overview

**RS Digital Hub** is a full-featured, production-ready **digital product marketplace** built with PHP & MySQL. It enables you to sell digital goods — templates, scripts, eBooks, courses, software — with secure payments, subscription management, automated invoicing, PDF document generation, and a complete admin dashboard.

> Built with ❤️ by **Rohit Kumar Saw**

---

## ✨ Features

### 🏪 Marketplace Core
| Feature | Description |
|---------|-------------|
| **Multi-Vendor System** | Admin + Developer roles with individual storefronts |
| **Product Management** | Add/edit/delete digital products with screenshots, categories, pricing |
| **Smart Search & Filters** | Search by keyword, filter by category, price, popularity |
| **Secure Downloads** | Token-based download links with expiry & download limits |

### 💳 Payments & Billing
| Feature | Description |
|---------|-------------|
| **Razorpay Integration** | Full payment gateway with test/live mode toggle |
| **Subscription Plans** | Recurring billing with feature-based tiered plans |
| **Coupon Engine** | Percentage/flat discounts with usage limits & expiry |
| **GST Invoicing** | Professional PDF invoices with GST, bank details, digital signature |

### 📄 Document Generation
| Feature | Description |
|---------|-------------|
| **Dynamic PDF Engine** | Generate documents with auto-stamps & signatures |
| **PDF Reports** | Export payments, orders, subscriptions, users & products |
| **Invoice System** | Auto-generated invoices with template system |

### 🔐 Security & Admin
| Feature | Description |
|---------|-------------|
| **Session Guard** | Simultaneous login limits with live tracking |
| **CSRF Protection** | Token validation on all POST handlers |
| **XSS Prevention** | Output sanitized via `htmlspecialchars()` |
| **Upload Sandbox** | `.htaccess` blocks PHP execution in uploads directory |
| **Admin Dashboard** | Real-time stats, order management, user management, settings |
| **Support Tickets** | Threaded messaging between users and admins |

---

## 🛠 Tech Stack

```
Backend   →  PHP 8.2+  |  PDO MySQL  |  OOP Architecture
Frontend  →  HTML5  |  CSS3  |  Vanilla JavaScript (ES6+)
PDF       →  TCPDF Engine
Payments  →  Razorpay API
Database  →  MySQL 5.7+  /  MariaDB 10.3+
Server    →  Apache with mod_rewrite
Security  →  CSRF Tokens  |  PDO Prepared Statements  |  Session Validation
```

---

## 📂 Project Structure

```
RS_DIGITAL_HUB/
│
├── 📄 .htaccess              # URL rewriting & security rules
├── ⚙️ config.php             # Core application configuration
├── 🔧 config_debug.php       # Local development debugging
├── 🔌 db.php                 # Smart DB auto-detection (local/production)
├── 🗄️ database.sql           # Database schema & seed data
├── 📄 DEPLOY.md              # Deployment guide
│
├── 📋 admin/                 # Administrator panel
│   ├── 🔄 ajax/              # Asynchronous request handlers
│   └── 🧩 includes/          # Admin layout components
│
├── 🎨 assets/                # Static assets (CSS, JS, Images)
├── 📦 classes/               # PHP OOP classes
├── 👨‍💻 developer/           # Developer dashboard & store
├── 🛠 includes/              # Core helpers & functions
├── 🧾 invoices/              # Invoice generation engine
│   └── 📐 templates/         # Invoice PDF templates
├── 📚 lib/tcpdf/             # TCPDF library
├── 📝 migrations/            # Database migration scripts
├── 📄 pdf/                   # PDF render engines
├── 🔄 processes/             # Form submission handlers
├── 📂 uploads/               # User uploaded content
│
├── 🏪 index.php              # Landing page
├── 📦 products.php           # Product listing
├── 🏷️ product.php           # Product detail
├── 🛒 store.php              # Digital storefront
├── 💳 payment-callback.php   # Payment webhook handler
├── 📥 download.php           # Secure file download
├── 🧾 invoice.php            # Invoice viewer
├── 📄 invoice-pdf.php        # PDF invoice download
└── ...
```

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- PHP Extensions: OpenSSL, JSON, PDO MySQL

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/Rohitkumarsaw/RS_DIGITAL_HUB.git
cd RS_DIGITAL_HUB

# 2. Import the database schema
mysql -u root -p your_database < database.sql

# 3. Configure database connection
#    → db.php auto-detects localhost vs production
#    → Localhost defaults to root (no password)
#    → Production uses your hosting credentials

# 4. Set up virtual host pointing to project root
#    Or place directly in your web server directory

# 5. Configure Razorpay API keys
#    → Admin Panel → Settings → Payment Gateway
```

### Default Admin Access
- **URL**: `http://your-domain/admin/`
- **Email**: Configured during setup
- **Password**: Configured during setup

---

## 🖼️ Screenshots

> _Coming soon — preview images of the landing page, admin dashboard, product management, and invoice system._

---

## 🔒 Security Features

| Category | Implementation |
|----------|---------------|
| **SQL Injection** | ✅ PDO prepared statements throughout |
| **Cross-Site Scripting** | ✅ `htmlspecialchars()` on all output |
| **Cross-Site Request Forgery** | ✅ CSRF tokens on every POST form |
| **Session Security** | ✅ HTTP-only cookies, login limits, session tracking |
| **File Uploads** | ✅ Sandboxed directory, type validation, execution blocked via `.htaccess` |
| **Error Exposure** | ✅ Hidden in production, verbose in debug mode |
| **Sensitive Files** | ✅ `.sql`, `.md`, `config` files blocked from direct URL access |

---

## 📊 Database

- **Engine**: MySQL / MariaDB
- **Tables**: Users, Products, Orders, Payments, Subscriptions, Coupons, Tickets, Invoices, Sessions, Categories
- **Smart Connection**: Auto-switches between local and production credentials via `db.php`
- **Migrations**: Versioned migration scripts in `/migrations/`

---

## 🤝 Contributing

Contributions are welcome! Feel free to open issues or submit pull requests.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

<p align="center">
  <b>All Rights Reserved</b> — Proprietary and confidential.
</p>

<p align="center">
  Copyright © 2026 <a href="https://github.com/Rohitkumarsaw">Rohit Kumar Saw</a>
</p>

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=2563EB&height=120&section=footer"/>
</p>
