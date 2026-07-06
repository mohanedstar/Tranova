# 🎓 Trinova - Student Internship Management Platform

<div align="center">

**A comprehensive platform for managing student internships with AI-powered features**

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)
![Tests](https://img.shields.io/badge/Tests-165+-4CAF50)
![AI](https://img.shields.io/badge/AI-Groq%20LLM-FF6B35)
![Security](https://img.shields.io/badge/Security-RBAC%20%2B%20Admin%20Review-00C853)

</div>

---

## 📖 Table of Contents

- [Project Overview](#-project-overview)
- [Features](#-features)
- [AI-Powered Features](#-ai-powered-features)
- [Admin Review System](#-admin-review-system)
- [University Email Validation](#-university-email-validation)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Deployment](#-deployment)

---

## 🎯 Project Overview

**Trinova** is a modern platform designed to streamline the management of student internships by connecting:

- 🎓 **Students** - Find and apply for opportunities, submit reports, view evaluations
- 🏢 **Providers** - Offer training positions, review applications, evaluate students
- 👨‍🏫 **Supervisors** - Monitor student progress, review reports, academic evaluations
- 🛡️ **Admins** - Manage the entire system, approve providers, generate certificates

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

### 👥 User Roles

| Role | Permissions | Special Requirements |
|------|-------------|---------------------|
| **Student** | Apply, submit reports, view evaluations, download certificates | Any email allowed |
| **Provider** | Create opportunities, review applications, evaluate students | **Requires admin approval** |
| **Supervisor** | Monitor students, review reports, academic evaluations | **University email required** |
| **Admin** | Full system management, approve providers, generate certificates | - |

### 💼 Core Features

- 📝 Internship opportunities management (CRUD + Close/Reopen)
- 📄 Application submission with CV upload
- 📊 Weekly reports tracking with file attachments
- ⭐ Multi-criteria evaluation system (Provider + Supervisor)
- 🏆 Professional PDF certificates (English)
- 🔔 Real-time notifications system
- 💬 Internal messaging system
- 👤 **Applicant profile viewing** (for providers)
- ⏰ **Late students identification** (for supervisors)
- 📈 Final grade calculation system

---

## 🤖 AI-Powered Features

Trinova integrates advanced AI capabilities using **Groq LLM** to help students write better internship reports:

### 🎯 AI Features

| Feature | Description | Endpoint |
|---------|-------------|----------|
| **Improve Report** | Enhance student reports with professional language | `POST /api/student/ai/reports/improve` |
| **Analyze Report** | Get quality score, strengths, weaknesses, and suggestions | `POST /api/student/ai/reports/analyze` |
| **Generate Report** | Create full report from bullet points | `POST /api/student/ai/reports/generate` |
| **Smart Suggestions** | Get topic suggestions based on major and week | `POST /api/student/ai/reports/suggest` |

### 🌍 Language Support

- ✅ **Automatic language detection** (Arabic/English)
- ✅ Responds in the same language as the input
- ✅ Supports mixed-language content

### 📊 AI Response Examples

**Improve Report:**

```json
{
    "original_content": "تعلمت Laravel اليوم",
    "improved_content": "خلال هذا اليوم، ركزت على تطوير مهاراتي في إطار عمل Laravel...",
    "detected_language": "arabic"
}
```

**Analyze Report:**

```json
{
    "quality_score": 85,
    "grade": "good",
    "strengths": ["محتوى جيد", "تنظيم واضح"],
    "weaknesses": ["يحتاج أمثلة عملية"],
    "improvements": ["أضف تفاصيل تقنية"]
}
```

**Generate Report:**

```json
{
    "input_points": ["تعلمت Laravel", "عملت على database"],
    "generated_report": "خلال هذا الأسبوع، ركزت على..."
}
```

**Smart Suggestions:**

```json
{
    "suggested_topics": ["تطوير APIs", "إدارة قواعد البيانات"],
    "suggested_tasks": ["بناء نموذج Student", "كتابة اختبارات"],
    "suggested_challenges": ["فهم العلاقات المعقدة"]
}
```

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
│  1️⃣ تسجيل مزود جديد                                     │
│     ↓                                                   │
│  2️⃣ account_status = 'pending_review'                   │
│     ↓                                                   │
│  3️⃣ محاولة تسجيل الدخول → ❌ 403 Forbidden              │
│     "حسابك قيد المراجعة من قبل الإدارة"                 │
│     ↓                                                   │
│  4️⃣ المدير يوافق على الحساب                             │
│     ↓                                                   │
│  5️⃣ account_status = 'active'                           │
│     ↓                                                   │
│  6️⃣ تسجيل الدخول → ✅ 200 OK                           │
│     ↓                                                   │
│  7️⃣ نشر فرص تدريبية → ✅ 201 Created                   │
└─────────────────────────────────────────────────────────┘
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

```json
{
    "message": "يجب استخدام بريد إلكتروني جامعي رسمي",
    "errors": {
        "email": ["The email must be a valid university email domain."]
    }
}
```

**✅ Accepted:**

```json
{
    "email": "ahmed@iugaza.edu.ps",
    "role": "supervisor"
}
```

Response: `201 Created` ✅

---

## 🛠️ Tech Stack

### Backend

- **Framework:** Laravel 11
- **Language:** PHP 8.3
- **Database:** MySQL 8.0
- **Authentication:** Laravel Sanctum
- **PDF Generation:** DomPDF
- **Testing:** Pest PHP (165+ tests)
- **AI Integration:** Groq API (Llama 3.3)

### AI & External Services

- **AI Provider:** Groq (Free tier, ultra-fast)
- **Model:** Llama 3.3 70B Versatile
- **Fallback:** Google Gemini API (optional)

---

## 📦 Installation

### Prerequisites

- PHP >= 8.3
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
GROQ_API_KEY=your_groq_api_key_here
GROQ_MODEL=llama-3.3-70b-versatile

# 6. Run migrations
php artisan migrate
php artisan db:seed

# 7. Create storage link
php artisan storage:link

# 8. Start server
php artisan serve
```

Visit: `http://127.0.0.1:8000`

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

### Main Endpoints

#### 🔐 Authentication

- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/profile` - Get profile

#### 📧 Email Verification

- `GET /api/email/verify/{id}/{hash}` - Verify email
- `POST /api/email/resend` - Resend verification

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

#### 🔔 Notifications

- `GET /api/notifications` - Get all notifications
- `GET /api/notifications/unread` - Get unread notifications
- `POST /api/notifications/{id}/read` - Mark as read
- `POST /api/notifications/read-all` - Mark all as read
- `DELETE /api/notifications/{id}` - Delete notification

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

#### 🛡️ Admin Management

- `GET /api/admin/providers` - List all providers
- `GET /api/admin/providers/pending` - List pending providers
- `POST /api/admin/providers/{id}/approve` - Approve provider
- `POST /api/admin/providers/{id}/reject` - Reject provider
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

### Test Coverage

| Category | Tests | Status |
|----------|-------|--------|
| Unit Tests | 6 | ✅ |
| Authentication | 4 | ✅ |
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
| Applicant Profile | 5 | ✅ |
| Late Students | 5 | ✅ |
| Reopen Opportunity | 5 | ✅ |
| University Email Validation | 5 | ✅ |
| AI Report Features | 33 | ✅ |
| **Total** | **165+** | ✅ **All Passing** |

### AI Features Testing

The AI tests use **Mockery** to mock the Groq API, ensuring:

- ✅ Fast execution (< 20 seconds for all tests)
- ✅ No API costs during testing
- ✅ Reliable and deterministic results
- ✅ Coverage of all edge cases

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
   - `DB_*` (database credentials)
   - `GROQ_API_KEY` (for AI features)
   - `GROQ_MODEL=llama-3.3-70b-versatile`
6. Click **Create Web Service**

📖 **Full Deployment Guide:** [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)

---

## 📄 License

This project is proprietary and confidential.

---

## 👥 Team

- **Development Team** - Trinova Platform

---

<div align="center">

**Made with ❤️ In TaqaT**

**Powered by Groq AI 🤖**

</div>
