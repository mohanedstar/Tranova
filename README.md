# 🎓 Trinova - Student Internship Management Platform

<div align="center">

**A comprehensive platform for managing student internships with AI-powered features, multilingual support, and professional certificate generation**

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)
![Tests](https://img.shields.io/badge/Tests-227+-4CAF50)
![AI](https://img.shields.io/badge/AI-Groq%20LLM-FF6B35)
![Languages](https://img.shields.io/badge/Languages-Arabic%20%7C%20English-007ACC)
![Security](https://img.shields.io/badge/Security-RBAC%20%2B%20Admin%20Review-00C853)

</div>

---

## 📖 Table of Contents

- [Project Overview](#-project-overview)
- [Features](#-features)
- [AI-Powered Features](#-ai-powered-features)
- [Multilingual Support](#-multilingual-support)
- [Admin Review System](#-admin-review-system)
- [Admin User Management](#-admin-user-management)
- [University Email Validation](#-university-email-validation)
- [Professional Certificates](#-professional-certificates)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Deployment](#-deployment)

---

## 🎯 Project Overview

**Trinova** is a modern platform designed to streamline the management of student internships by connecting:

- 🎓 **Students** - Find and apply for opportunities, submit reports, view evaluations, get AI-powered writing assistance
- 🏢 **Providers** - Offer training positions, review applications, evaluate students (requires admin approval)
- 👨‍🏫 **Supervisors** - Monitor student progress, review reports, academic evaluations
- 🛡️ **Admins** - Full system management, approve providers, manage all users, generate certificates

---

## ✨ Features

### 🔐 Authentication & Security

- JWT-based authentication (Laravel Sanctum)
- Email verification system with signed URLs
- Password reset functionality
- Role-based access control (RBAC) with 4 roles
- **Admin review system for providers** (new accounts require approval)
- **University email validation for supervisors**
- Rate limiting on sensitive endpoints
- XSS and SQL injection protection
- **Dynamic language detection per user**

### 👥 User Roles

| Role | Permissions | Special Requirements |
|------|-------------|---------------------|
| **Student** | Apply, submit reports, view evaluations, download certificates | Any email allowed |
| **Provider** | Create opportunities, review applications, evaluate students | **Requires admin approval** |
| **Supervisor** | Monitor students, review reports, academic evaluations | **University email required** |
| **Admin** | Full system management, approve providers, manage all users | - |

### 💼 Core Features

- 📝 Internship opportunities management (CRUD + Close/Reopen)
- 📄 Application submission with CV upload
- 📊 Weekly reports tracking with file attachments
- ⭐ Multi-criteria evaluation system (Provider + Supervisor)
- 🏆 Professional PDF certificates (English with full Arabic support)
- 🔔 Real-time notifications system
- 💬 Internal messaging system
- 👤 **Applicant profile viewing** (for providers)
- ⏰ **Late students identification** (for supervisors)
- 📈 Final grade calculation system
- 🎯 **Final evaluation records management**

---

## 🤖 AI-Powered Features

Trinova integrates advanced AI capabilities using **Groq LLM** to help students and supervisors write and review better internship reports.

### 🎯 AI Features

AI features are available for both **Students** and **Supervisors**:

#### For Students:

| Feature | Description | Endpoint |
|---------|-------------|----------|
| **Improve Report** | Enhance student reports with professional language | `POST /api/student/ai/reports/improve` |
| **Analyze Report** | Get quality score, strengths, weaknesses, and suggestions | `POST /api/student/ai/reports/analyze` |
| **Generate Report** | Create full report from bullet points | `POST /api/student/ai/reports/generate` |
| **Smart Suggestions** | Get topic suggestions based on major and week | `POST /api/student/ai/reports/suggest` |

#### For Supervisors:

| Feature | Description | Endpoint |
|---------|-------------|----------|
| **Improve Report** | Review and enhance student reports | `POST /api/supervisor/ai/reports/improve` |
| **Analyze Report** | Analyze student reports before grading | `POST /api/supervisor/ai/reports/analyze` |
| **Generate Report** | Create example reports for guidance | `POST /api/supervisor/ai/reports/generate` |
| **Smart Suggestions** | Provide topic suggestions to students | `POST /api/supervisor/ai/reports/suggest` |

**Note:** Supervisors can use the same AI features to help review and analyze student reports more effectively.

### 🌍 Language Support (AI)

- ✅ **Automatic language detection** (Arabic/English)
- ✅ Responds in the same language as the input
- ✅ Supports mixed-language content
- ✅ Context-aware professional terminology

### 📊 AI Response Examples

**Improve Report:**

```json
{
    "original_content": "تعلمت Laravel اليوم",
    "improved_content": "خلال هذا اليوم، ركزت على تطوير مهاراتي في إطار عمل Laravel...",
    "detected_language": "arabic",
    "ai_model": "llama-3.3-70b-versatile"
}
```

**Analyze Report:**

```json
{
    "quality_score": 85,
    "grade": "good",
    "strengths": ["محتوى جيد", "تنظيم واضح"],
    "weaknesses": ["يحتاج أمثلة عملية"],
    "improvements": ["أضف تفاصيل تقنية"],
    "criteria_scores": {
        "content_quality": 85,
        "structure": 80,
        "language": 90,
        "professionalism": 85
    }
}
```

**Generate Report:**

```json
{
    "input_points": ["تعلمت Laravel", "عملت على database"],
    "generated_report": "خلال هذا الأسبوع، ركزت على...",
    "report_statistics": {
        "word_count": 85,
        "sentence_count": 5,
        "estimated_reading_time_minutes": 1
    }
}
```
Trinova integrates advanced AI capabilities using **Groq LLM** to help students write better internship reports:

### 🎯 AI Features

| Feature | Description | Endpoint |
|---------|-------------|----------|
| **Improve Report** | Enhance student reports with professional language | `POST /api/student/ai/reports/improve` |
| **Analyze Report** | Get quality score, strengths, weaknesses, and suggestions | `POST /api/student/ai/reports/analyze` |
| **Generate Report** | Create full report from bullet points | `POST /api/student/ai/reports/generate` |
| **Smart Suggestions** | Get topic suggestions based on major and week | `POST /api/student/ai/reports/suggest` |

### 🌍 Language Support (AI)

- ✅ **Automatic language detection** (Arabic/English)
- ✅ Responds in the same language as the input
- ✅ Supports mixed-language content
- ✅ Context-aware professional terminology

### 📊 AI Response Examples

**Improve Report:**

```json
{
    "original_content": "تعلمت Laravel اليوم",
    "improved_content": "خلال هذا اليوم، ركزت على تطوير مهاراتي في إطار عمل Laravel...",
    "detected_language": "arabic",
    "ai_model": "llama-3.3-70b-versatile"
}
```

**Analyze Report:**

```json
{
    "quality_score": 85,
    "grade": "good",
    "strengths": ["محتوى جيد", "تنظيم واضح"],
    "weaknesses": ["يحتاج أمثلة عملية"],
    "improvements": ["أضف تفاصيل تقنية"],
    "criteria_scores": {
        "content_quality": 85,
        "structure": 80,
        "language": 90,
        "professionalism": 85
    }
}
```

**Generate Report:**

```json
{
    "input_points": ["تعلمت Laravel", "عملت على database"],
    "generated_report": "خلال هذا الأسبوع، ركزت على...",
    "report_statistics": {
        "word_count": 85,
        "sentence_count": 5,
        "estimated_reading_time_minutes": 1
    }
}
```

---

## 🌍 Multilingual Support

Trinova provides **dynamic language support** that adapts to each user's preference:

### 🎯 Language Detection Priority

| Priority | Source | Example |
|----------|--------|---------|
| 1️⃣ | Query Parameter | `?lang=ar` |
| 2️⃣ | Custom Header | `X-Language: en` |
| 3️⃣ | User Preference | `preferred_language` in database |
| 4️⃣ | Accept-Language Header | `Accept-Language: ar,en;q=0.9` |
| 5️⃣ | Default from .env | `APP_LOCALE=ar` |

### 🛠️ Implementation

- **Custom Middleware:** `SetLocale` - Automatically detects and applies the correct language
- **User Preferences:** Stored in `users.preferred_language` field
- **User Endpoint:** `POST /api/user/language` - Change language preference
- **Language Files:** Organized in `lang/ar/` and `lang/en/`
- **Translation Helper:** `__('messages.key')` throughout the codebase

### 🎨 Custom Artisan Commands

```bash
# Scan project for Arabic texts
php artisan lang:scan --path=app

# Preview translation changes
php artisan lang:preview --controller=AuthController.php

# Apply translations with backup
php artisan lang:replace --backup --force

# Generate translation keys
php artisan lang:generate --path=app
```

### 🌐 Example Usage

**Change user's language:**

```http
POST http://127.0.0.1:8000/api/user/language
Authorization: Bearer {token}
Content-Type: application/json

{
    "language": "en"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Language updated successfully",
    "language": "en"
}
```

### 📚 Supported Messages Categories

- ✅ **auth** - Authentication messages (login, register, etc.)
- ✅ **validation** - Validation error messages
- ✅ **opportunity** - Opportunity-related messages
- ✅ **application** - Application workflow messages
- ✅ **report** - Weekly report messages
- ✅ **evaluation** - Evaluation messages
- ✅ **message** - Messaging system messages
- ✅ **notification** - Notification messages
- ✅ **ai** - AI feature messages
- ✅ **admin** - Admin management messages
- ✅ **certificate** - Certificate messages
- ✅ **general** - General system messages

---

## 🛡️ Admin Review System

### Provider Approval Workflow

1. **Registration**: Provider registers → Account status: `pending_review`
2. **Email Verification**: Provider verifies email
3. **Admin Review**: Admin reviews and approves/rejects
4. **Activation**: Account status changes to `active`
5. **Access Granted**: Provider can now create opportunities

### Admin Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/admin/providers` | List all providers |
| `GET /api/admin/providers/pending` | List providers awaiting approval |
| `POST /api/admin/providers/{id}/approve` | Approve a provider |
| `POST /api/admin/providers/{id}/reject` | Reject a provider (with reason) |

### Account Status Flow

```
┌─────────────────────────────────────────────────────────┐
│  1️⃣ Provider registers                                   │
│     ↓                                                   │
│  2️⃣ account_status = 'pending_review'                   │
│     ↓                                                   │
│  3️⃣ Login attempt → ❌ 403 Forbidden                    │
│     "Your account is pending admin review"              │
│     ↓                                                   │
│  4️⃣ Admin approves account                              │
│     ↓                                                   │
│  5️⃣ account_status = 'active'                           │
│     ↓                                                   │
│  6️⃣ Login → ✅ 200 OK                                   │
│     ↓                                                   │
│  7️⃣ Publish opportunities → ✅ 201 Created              │
└─────────────────────────────────────────────────────────┘
```

### Default Account Status by Role

| Role | Default Status | Requires Approval? |
|------|----------------|-------------------|
| **Student** | `active` | ❌ No |
| **Provider** | `pending_review` | ✅ Yes |
| **Supervisor** | `active` | ❌ No (university email verified) |
| **Admin** | `active` | ❌ No |

---

## 👨‍💼 Admin User Management

Admins have full control over all users in the system:

### 🔧 User Management Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/users` | List all users (with filters) |
| GET | `/api/admin/users/{id}` | View user details |
| POST | `/api/admin/users` | Create new user |
| PUT | `/api/admin/users/{id}` | Update user |
| DELETE | `/api/admin/users/{id}` | Delete user |
| POST | `/api/admin/users/{id}/suspend` | Suspend user account |
| POST | `/api/admin/users/{id}/activate` | Activate user account |
| POST | `/api/admin/users/{id}/reset-password` | Reset user password |

### 🎯 Features

- **Create users** with any role (student, provider, supervisor, admin)
- **Search & filter** by role, status, name, email
- **Suspend accounts** - Prevent login without deletion
- **Activate accounts** - Restore suspended accounts
- **Reset passwords** - Force password change (invalidates all tokens)
- **Self-protection** - Cannot delete/suspend own account
- **Cascade deletion** - Automatically removes related records

### 📋 Example: Create Student

```http
POST http://127.0.0.1:8000/api/admin/users
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "name": "Ahmed Mohamed",
    "email": "ahmed@university.edu",
    "password": "password123",
    "phone": "0591234567",
    "role": "student",
    "student_id": "20240999",
    "major": "IT",
    "university": "Islamic University",
    "year_of_study": "3"
}
```

### 📋 Example: Suspend User

```http
POST http://127.0.0.1:8000/api/admin/users/5/suspend
Authorization: Bearer {admin_token}
```

**Response:**

```json
{
    "success": true,
    "message": "User account suspended",
    "data": {
        "id": 5,
        "name": "John Doe",
        "account_status": "suspended"
    }
}
```

---

## 🎓 University Email Validation

### Supervisor Registration Requirements

Supervisors must register with approved university email domains:

| University | Email Domain |
|------------|--------------|
| Islamic University of Gaza | `@iugaza.edu.ps` |
| Al-Azhar University | `@alazhar.edu.ps` |
| University of Palestine | `@up.edu.ps` |
| Al-Aqsa University | `@alaqsa.edu.ps` |
| University of Science & Technology | `@uast.edu.ps` |

### Validation Example

**❌ Rejected:**

```json
{
    "email": "ahmed@gmail.com",
    "role": "supervisor"
}
```

Response: `422 Unprocessable Entity`

**✅ Accepted:**

```json
{
    "email": "ahmed@iugaza.edu.ps",
    "role": "supervisor"
}
```

Response: `201 Created` ✅

### Custom Validation Rule

- **Rule:** `UniversityEmail` (in `app/Rules/`)
- **Configuration:** `config/universities.php`
- **Strict mode:** Rejects non-university emails automatically

---

## 🏆 Professional Certificates

### ✨ Certificate Features

- **Professional Design** - A4 landscape with elegant borders
- **Full Arabic Support** - Using DejaVu Sans font
- **Student Information** - Name, ID, Major, University, Year
- **Training Details** - Opportunity title, provider, dates, hours
- **Grade Display** - Final grade with status (Excellent/Very Good/Good/Pass/Fail)
- **Unique Certificate Number** - Format: `TRN-{year}-{sequence}-{random}`
- **Digital Verification** - Certificate number can be verified online
- **Signatures** - Training provider and academic supervisor

### 📄 Certificate Content

```
TRINOVA PLATFORM
Certificate of Internship Completion

This is to certify that
[Ahmed Mohamed]
Student ID: 20240001 | Major: IT | University: Islamic University

has successfully completed the internship program in
"Laravel Backend"
at Tech Corp
during the period from 2026/07/03 to 2026/10/03
with a total of 60 training hours

Final Grade: 89.46 / 100 - Very Good
```

### 🎨 Certificate Design Elements

- **Gold Border** (`#c9a961`) - Professional accent
- **Navy Blue** (`#1e3a5f`) - Brand color
- **Black Text** (`#000000`) - Clear readability
- **DejaVu Sans Font** - Full Arabic/English support
- **QR Code Placeholder** - For future verification

### 🔧 Certificate Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/student/certificates` | List student's certificates |
| `GET /api/student/certificates/download` | Download as PDF |
| `GET /api/student/certificates/preview` | Preview in browser |
| `GET /api/admin/certificates` | List all certificates (admin) |
| `POST /api/admin/records/{id}/generate-certificate` | Generate certificate |
| `GET /api/admin/students/{id}/certificate` | Download specific certificate |

---

## 🛠️ Tech Stack

### Backend

- **Framework:** Laravel 12
- **Language:** PHP 8.2
- **Database:** MySQL 8.0
- **Authentication:** Laravel Sanctum
- **PDF Generation:** DomPDF with DejaVu Sans font
- **Testing:** Pest PHP (227+ tests)
- **AI Integration:** Groq API (Llama 3.3)
- **Multilingual:** Custom SetLocale middleware + translation files

### AI & External Services

- **AI Provider:** Groq (Free tier, ultra-fast)
- **Model:** Llama 3.3 70B Versatile
- **Fallback:** Google Gemini API (optional)

### Key Components

| Component | Purpose |
|-----------|---------|
| `SetLocale` Middleware | Dynamic language detection per request |
| `LanguageMapper` Service | Arabic ↔ Translation keys mapping |
| `CertificateService` | Professional PDF generation |
| `UniversityEmail` Rule | Supervisor email validation |
| `CheckRole` Middleware | Role-based access control |
| `GeminiService` | AI integration with Groq/Gemini |

### Custom Artisan Commands

| Command | Purpose |
|---------|---------|
| `lang:scan` | Scan project for Arabic texts |
| `lang:preview` | Preview translation changes |
| `lang:replace` | Apply translations safely |
| `lang:generate` | Generate translation keys |

---

## 📦 Installation

### Prerequisites

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Groq API Key (free from [console.groq.com](https://console.groq.com))

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

# 5. Configure AI (Groq)
AI_PROVIDER=groq
GROQ_API_KEY=your_groq_api_key_here
GROQ_MODEL=llama-3.3-70b-versatile

# 6. Configure Language
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_SUPPORTED_LOCALES=en,ar

# 7. Run migrations
php artisan migrate
php artisan db:seed

# 8. Create storage link
php artisan storage:link

# 9. Start server
php artisan serve
```

Visit: `http://127.0.0.1:8000`

### 📝 Environment Variables Reference

```env
# Application
APP_NAME=Trinova
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Language
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_SUPPORTED_LOCALES=en,ar

# AI
AI_PROVIDER=groq
GROQ_API_KEY=your_groq_api_key
GROQ_MODEL=llama-3.3-70b-versatile

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trinova
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

## 📡 API Documentation

### Base URL

```
http://127.0.0.1:8000/api
```

### Authentication

```
Authorization: Bearer {token}
```

### Language Header (Optional)

```
X-Language: en
Accept-Language: ar,en;q=0.9
```

### Main Endpoints

#### 🔐 Authentication

- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/profile` - Get profile
- `POST /api/user/language` - Change language preference

#### 📧 Email Verification

- `GET /api/email/verify/{id}/{hash}` - Verify email
- `POST /api/email/resend` - Resend verification
- `GET /api/email/verify-notice` - Verification notice

#### 💼 Opportunities

- `GET /api/opportunities` - List opportunities (with search & filters)
- `GET /api/opportunities/{id}` - Get opportunity details
- `POST /api/provider/opportunities` - Create opportunity
- `PUT /api/provider/opportunities/{id}` - Update opportunity
- `POST /api/provider/opportunities/{id}/close` - Close opportunity
- `POST /api/provider/opportunities/{id}/reopen` - Reopen opportunity
- `GET /api/provider/opportunities` - List provider's opportunities

#### 📄 Applications

- `POST /api/student/opportunities/{id}/apply` - Apply (with CV upload)
- `GET /api/student/applications` - Track my applications
- `POST /api/student/applications/{id}/withdraw` - Withdraw application
- `GET /api/provider/opportunities/{id}/applications` - List applicants
- `GET /api/provider/applicants/{studentId}/profile` - View applicant profile
- `POST /api/provider/applications/{id}/review` - Accept/Reject application

#### 📊 Reports

- `POST /api/student/reports` - Submit report (with attachments)
- `GET /api/student/reports` - Get my reports
- `GET /api/supervisor/reports` - Get students' reports
- `POST /api/supervisor/reports/{id}/review` - Review report
- `GET /api/supervisor/students/late` - Identify late students

#### ⭐ Evaluations

- `POST /api/provider/evaluations` - Provider evaluation
- `POST /api/supervisor/evaluations` - Supervisor evaluation
- `GET /api/student/evaluations` - Get my evaluations
- `POST /api/admin/students/{id}/opportunities/{id}/calculate` - Calculate final grade

#### 🏆 Certificates

- `GET /api/student/certificates` - List certificates
- `GET /api/student/certificates/download` - Download certificate
- `GET /api/student/certificates/preview` - Preview certificate
- `GET /api/admin/certificates` - List all certificates (admin)
- `POST /api/admin/records/{id}/generate-certificate` - Generate certificate
- `GET /api/admin/students/{id}/certificate` - Download specific certificate

#### 🔔 Notifications

- `GET /api/notifications` - Get all notifications
- `GET /api/notifications/unread` - Get unread notifications
- `POST /api/notifications/{id}/read` - Mark as read
- `POST /api/notifications/read-all` - Mark all as read
- `DELETE /api/notifications/{id}` - Delete notification
- `DELETE /api/notifications` - Clear all notifications

#### 💬 Messages

- `POST /api/messages` - Send message
- `GET /api/messages/inbox` - Get inbox
- `GET /api/messages/sent` - Get sent messages
- `POST /api/messages/{id}/read` - Mark as read

#### 🤖 AI Features (Student Only)

- `POST /api/student/ai/reports/improve` - Improve report
- `POST /api/student/ai/reports/analyze` - Analyze report
- `POST /api/student/ai/reports/generate` - Generate from points
- `POST /api/student/ai/reports/suggest` - Get smart suggestions

#### 🛡️ Admin Provider Management

- `GET /api/admin/providers` - List all providers
- `GET /api/admin/providers/pending` - List pending providers
- `POST /api/admin/providers/{id}/approve` - Approve provider
- `POST /api/admin/providers/{id}/reject` - Reject provider

#### 👨‍💼 Admin User Management (NEW)

- `GET /api/admin/users` - List all users (with filters: role, status, search)
- `GET /api/admin/users/{id}` - View user details
- `POST /api/admin/users` - Create new user (any role)
- `PUT /api/admin/users/{id}` - Update user
- `DELETE /api/admin/users/{id}` - Delete user
- `POST /api/admin/users/{id}/suspend` - Suspend user account
- `POST /api/admin/users/{id}/activate` - Activate user account
- `POST /api/admin/users/{id}/reset-password` - Reset user password

#### 🔧 Admin System

- `GET /api/admin/students` - List all students
- `GET /api/admin/supervisors` - List all supervisors
- `POST /api/admin/assign-supervisor` - Assign supervisor to student
- `POST /api/admin/records/{id}/approve` - Approve internship record
- `GET /api/admin/statistics` - Get system statistics

📖 **Full API Documentation:** [docs/API.md](docs/API.md)

---

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Admin user management tests
php artisan test tests/Feature/AdminUserControllerTest.php

# Admin provider management tests
php artisan test tests/Feature/AdminProviderControllerTest.php

# AI features tests
php artisan test tests/Feature/AIReportTest.php
```

### Test Coverage

| Category | Tests | Status |
|----------|-------|--------|
| Unit Tests | 6 | ✅ |
| Authentication | 8 | ✅ |
| Opportunities | 9 | ✅ |
| Role Permissions | 21 | ✅ |
| Weekly Reports | 9 | ✅ |
| Evaluations | 13 | ✅ |
| Certificates | 12 | ✅ |
| Notifications | 17 | ✅ |
| Messages | 10 | ✅ |
| Password Reset | 11 | ✅ |
| Email Verification | 19 | ✅ |
| Admin Provider Review | 11 | ✅ |
| **Admin User Management** | **51** | ✅ **NEW** |
| **Admin Provider Management** | **14** | ✅ **NEW** |
| Applicant Profile | 5 | ✅ |
| Late Students | 5 | ✅ |
| Reopen Opportunity | 5 | ✅ |
| University Email Validation | 5 | ✅ |
| AI Report Features | 35 | ✅ |
| **Total** | **227+** | ✅ **All Passing** |

### 🧪 Test Environment Configuration

Tests run with `APP_LOCALE=ar` by default (configured in `phpunit.xml`). The `SetLocale` middleware is disabled in testing environment to ensure consistent results.

### AI Features Testing

The AI tests use **Mockery** to mock the Groq API, ensuring:

- ✅ Fast execution (< 20 seconds for all tests)
- ✅ No API costs during testing
- ✅ Reliable and deterministic results
- ✅ Coverage of all edge cases

### Admin User Management Testing

Comprehensive test coverage includes:

- ✅ List all users with filters
- ✅ View user details
- ✅ Create users (student, provider, supervisor, admin)
- ✅ Update user information
- ✅ Delete users (with cascade deletion)
- ✅ Suspend/activate accounts
- ✅ Reset passwords (invalidate all tokens)
- ✅ Role-based access control
- ✅ Self-protection (cannot delete/suspend own account)
- ✅ Integration workflow tests

---

## 🚀 Deployment

### Deploy to Render

1. Create account on [render.com](https://render.com)
2. Click **New +** → **Web Service**
3. Connect your GitHub repository
4. Configure:
   - **Build Command:** `composer install --no-dev && php artisan config:cache`
   - **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
5. Add environment variables:
   - `APP_KEY`
   - `APP_LOCALE=ar`
   - `APP_SUPPORTED_LOCALES=en,ar`
   - `DB_*` (database credentials)
   - `GROQ_API_KEY` (for AI features)
   - `GROQ_MODEL=llama-3.3-70b-versatile`
6. Click **Create Web Service**

### Environment Variables for Production

```env
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_SUPPORTED_LOCALES=en,ar
AI_PROVIDER=groq
GROQ_API_KEY=your_production_groq_key
GROQ_MODEL=llama-3.3-70b-versatile
```

📖 **Full Deployment Guide:** [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)

---

## 📚 Documentation

The project includes comprehensive documentation:

- 📖 **[docs/API.md](docs/API.md)** - Complete API reference
- 🎨 **[docs/FRONTEND.md](docs/FRONTEND.md)** - Frontend integration guide
- 🚀 **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Deployment guide
- 📊 **[docs/PROJECT_SUMMARY.md](docs/PROJECT_SUMMARY.md)** - Project overview

---

## 📄 License

This project is proprietary and confidential.

---

## 👥 Team

- **Development Team** - Trinova Platform

---

<div align="center">

**📖 Back to [README.md](README.md)**

**Made with ❤️ In TaqaT**

**Powered by Groq AI 🤖**

**Supports: العربية 🇸🇦 | English 🇬🇧**

</div>
