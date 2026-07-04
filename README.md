# 🎓 Trinova - Student Internship Management Platform

<div align="center">

**A comprehensive platform for managing student internships**

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)
![Tests](https://img.shields.io/badge/Tests-131+-4CAF50)

</div>

---

## 📖 Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Deployment](#deployment)

---

## 🎯 Project Overview

**Trinova** is a modern platform designed to streamline the management of student internships by connecting:

- 🎓 **Students** - Find and apply for opportunities
- 🏢 **Providers** - Offer training positions
- 👨‍🏫 **Supervisors** - Monitor student progress
- 🛡️ **Admins** - Manage the entire system

---

## ✨ Features

### 🔐 Authentication & Security
- JWT-based authentication (Laravel Sanctum)
- Email verification system
- Password reset functionality
- Role-based access control (RBAC)

### 👥 User Roles
| Role | Permissions |
|------|-------------|
| **Student** | Apply, submit reports, view evaluations, download certificates |
| **Provider** | Create opportunities, review applications, evaluate students |
| **Supervisor** | Monitor students, review reports, academic evaluations |
| **Admin** | Full system management, generate certificates |

### 💼 Core Features
- 📝 Internship opportunities management
- 📄 Application submission with CV upload
- 📊 Weekly reports tracking
- ⭐ Multi-criteria evaluation system
- 🏆 Professional PDF certificates (English)
- 🔔 Real-time notifications
- 💬 Internal messaging system

---

## 🛠️ Tech Stack

### Backend
- **Framework:** Laravel 11
- **Language:** PHP 8.3
- **Database:** MySQL 8.0
- **Authentication:** Laravel Sanctum
- **PDF:** DomPDF
- **Testing:** Pest PHP (131+ tests)

---

## 📦 Installation

### Prerequisites
- PHP >= 8.3
- Composer
- MySQL >= 8.0

### Steps

```bash
# 1. Clone repository
git clone https://github.com/mohanedstar/Tranova.git
cd Trinova

# 2. Install dependencies
composer install

# 3. Configure environment
copy .env.example .env
php artisan key:generate

# 4. Configure database in .env
DB_DATABASE=trinova
DB_USERNAME=root
DB_PASSWORD=your_password

# 5. Run migrations
php artisan migrate
php artisan db:seed

# 6. Create storage link
php artisan storage:link

# 7. Start server
php artisan serve


Visit: http://127.0.0.1:8000


📡 API Documentation
Base URL
http://127.0.0.1:8000/api

Authentication
Authorization: Bearer {token}

Main Endpoints

🔐 Authentication
POST /api/register - Register new user
POST /api/login - Login
POST /api/logout - Logout
GET /api/profile - Get profile

📧 Email Verification
GET /api/email/verify/{id}/{hash} - Verify email
POST /api/email/resend - Resend verification

💼 Opportunities
GET /api/opportunities - List opportunities
POST /api/provider/opportunities - Create opportunity
POST /api/student/opportunities/{id}/apply - Apply

📊 Reports
POST /api/student/reports - Submit report
GET /api/student/reports - Get reports
POST /api/supervisor/reports/{id}/review - Review report

⭐ Evaluations
POST /api/provider/evaluations - Provider evaluation
POST /api/supervisor/evaluations - Supervisor evaluation

🏆 Certificates
GET /api/student/certificates - List certificates
GET /api/student/certificates/download - Download
POST /api/admin/records/{id}/generate-certificate - Generate

🔔 Notifications
GET /api/notifications - Get all
POST /api/notifications/{id}/read - Mark as read

💬 Messages
POST /api/messages - Send message
GET /api/messages/inbox - Get inbox

📖 Full API Documentation: docs/API.md

🧪 Testing

Run All Tests

php artisan test

Test Coverage
Category          Tests           Status
Unit Tests          6               ✅
Authentication      4               ✅
Opportunities       9               ✅
Role Permissions    21              ✅
Weekly Reports      9               ✅
Evaluations         13              ✅
Certificates        12              ✅
Notifications       17              ✅
Messages            10              ✅
Password Reset      11              ✅
Email Verification  19              ✅
Total               131+            ✅ All Passing


🚀 Deployment
Deploy to Render
Create account on render.com
Click New + → Web Service
Connect your GitHub repository
Configure:
Build Command: composer install --no-dev && php artisan config:cache
Start Command: php artisan serve --host=0.0.0.0 --port=$PORT
Add environment variables
Click Create Web Service
📖 Full Deployment Guide: docs/DEPLOYMENT.md


 License
This project is proprietary and confidential.

👥 Team
Development Team - Trinova Platform

