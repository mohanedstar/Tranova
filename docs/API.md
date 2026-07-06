# 📡 Trinova API Documentation

<div align="center">

**Complete API Reference for Trinova Platform with AI-Powered Features**

![API](https://img.shields.io/badge/API-RESTful-4CAF50?style=flat-square)
![Auth](https://img.shields.io/badge/Auth-Sanctum-FF2D20?style=flat-square)
![Version](https://img.shields.io/badge/Version-1.0-007ACC?style=flat-square)
![AI](https://img.shields.io/badge/AI-Groq%20LLM-FF6B35?style=flat-square)
![Security](https://img.shields.io/badge/Security-RBAC%20%2B%20Admin%20Review-00C853?style=flat-square)

</div>

---

## 📖 Table of Contents

- [Base URL](#-base-url)
- [Authentication](#-authentication)
- [Response Format](#-response-format)
- [Authentication Endpoints](#-authentication-endpoints)
- [Email Verification](#-email-verification)
- [Password Reset](#-password-reset)
- [Opportunities](#-opportunities)
- [Applications](#-applications)
- [Weekly Reports](#-weekly-reports)
- [Evaluations](#-evaluations)
- [Certificates](#-certificates)
- [Notifications](#-notifications)
- [Messages](#-messages)
- [AI Features](#-ai-features)
- [Admin Management](#-admin-management)
- [Error Responses](#-error-responses)
- [Rate Limiting](#-rate-limiting)

---

## 🌐 Base URL

### Development

```
http://127.0.0.1:8000/api
```

### Production

```
https://your-domain.com/api
```

---

## 🔐 Authentication

All protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

### How to get a token

1. Register a new user via `/api/register`
2. Verify email via the link sent to email
3. Login via `/api/login`
4. Copy the `token` from the response

---

## 📦 Response Format

### Success Response

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": { ... }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

---

## 🔐 Authentication Endpoints

### Register New User

**Endpoint:** `POST /api/register`

**Auth Required:** ❌ No

**Request Body (Student):**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+970591234567",
    "role": "student",
    "student_id": "20240001",
    "major": "Computer Science",
    "university": "Islamic University",
    "year_of_study": "3"
}
```

**Request Body (Provider):**

```json
{
    "name": "Company HR",
    "email": "hr@company.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+970591234567",
    "role": "provider",
    "organization_name": "Tech Corp",
    "organization_type": "company",
    "address": "Gaza City",
    "city": "Gaza"
}
```

**Request Body (Supervisor - University Email Required):**

```json
{
    "name": "Dr. Ahmed",
    "email": "ahmed@iugaza.edu.ps",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+970591234567",
    "role": "supervisor",
    "employee_id": "EMP001",
    "department": "Computer Science",
    "academic_title": "professor"
}
```

**Roles and Required Fields:**

| Role | Required Fields | Special Requirements |
|------|-----------------|---------------------|
| `student` | `student_id`, `major`, `university`, `year_of_study` | Any email allowed |
| `provider` | `organization_name`, `organization_type`, `address`, `city` | **Requires admin approval** |
| `supervisor` | `employee_id`, `department`, `academic_title` | **University email required** |

**Success Response (201) - Student:**

```json
{
    "message": "تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني.",
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "student"
    },
    "account_status": "active",
    "email_verification_required": true
}
```

**Success Response (201) - Provider:**

```json
{
    "message": "تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني أولاً، ثم سيتم مراجعة حسابك من قبل الإدارة. سيتم إعلامك بالبريد عند الموافقة.",
    "token": "2|xyz789...",
    "user": {
        "id": 2,
        "name": "Company HR",
        "email": "hr@company.com",
        "role": "provider"
    },
    "account_status": "pending_review",
    "email_verification_required": true
}
```

**Error Response (422) - Supervisor with non-university email:**

```json
{
    "message": "The email field must be a valid university email domain.",
    "errors": {
        "email": ["يجب استخدام بريد إلكتروني جامعي رسمي. النطاقات المسموحة: iugaza.edu.ps, alazhar.edu.ps, up.edu.ps"]
    }
}
```

---

### Login

**Endpoint:** `POST /api/login`

**Auth Required:** ❌ No

**Request Body:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (200):**

```json
{
    "message": "تم تسجيل الدخول بنجاح",
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "student"
    },
    "email_verified": true
}
```

**Error Response (403) - Email not verified:**

```json
{
    "message": "يرجى التحقق من بريدك الإلكتروني أولاً",
    "email_verification_required": true
}
```

**Error Response (403) - Provider account pending review:**

```json
{
    "message": "حسابك قيد المراجعة من قبل الإدارة. سيتم إعلامك عند الموافقة.",
    "account_status": "pending_review"
}
```

**Error Response (403) - Provider account rejected:**

```json
{
    "message": "تم رفض حسابك. بيانات المؤسسة غير مكتملة.",
    "account_status": "rejected",
    "rejection_reason": "بيانات المؤسسة غير مكتملة."
}
```

---

### Logout

**Endpoint:** `POST /api/logout`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "message": "تم تسجيل الخروج بنجاح"
}
```

---

### Get Profile

**Endpoint:** `GET /api/profile`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "student",
        "account_status": "active",
        "student": {
            "student_id": "20240001",
            "major": "IT",
            "university": "Test University"
        }
    },
    "email_verified": true
}
```

---

## 📧 Email Verification

### Verify Email

**Endpoint:** `GET /api/email/verify/{id}/{hash}`

**Auth Required:** ❌ No (uses signed URL)

**Success Response (200):**

```json
{
    "message": "تم التحقق من بريدك الإلكتروني بنجاح!",
    "verified": true
}
```

---

### Resend Verification Email

**Endpoint:** `POST /api/email/resend`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "message": "تم إعادة إرسال رابط التحقق"
}
```

---

### Verification Notice

**Endpoint:** `GET /api/email/verify-notice`

**Auth Required:** ❌ No

**Response (200):**

```json
{
    "message": "يرجى التحقق من بريدك الإلكتروني. تم إرسال رابط التحقق إليك."
}
```

---

## 🔑 Password Reset

### Request Reset Link

**Endpoint:** `POST /api/password/forgot`

**Auth Required:** ❌ No

**Request Body:**

```json
{
    "email": "john@example.com"
}
```

**Success Response (200):**

```json
{
    "message": "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني"
}
```

---

### Verify Reset Token

**Endpoint:** `POST /api/password/verify-token`

**Auth Required:** ❌ No

**Request Body:**

```json
{
    "token": "reset-token-123",
    "email": "john@example.com"
}
```

**Success Response (200):**

```json
{
    "valid": true
}
```

---

### Reset Password

**Endpoint:** `POST /api/password/reset`

**Auth Required:** ❌ No

**Request Body:**

```json
{
    "token": "reset-token-123",
    "email": "john@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Success Response (200):**

```json
{
    "message": "تم إعادة تعيين كلمة المرور بنجاح"
}
```

---

## 💼 Opportunities

### List All Opportunities

**Endpoint:** `GET /api/opportunities`

**Auth Required:** ❌ No

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search in title/description |
| `major` | string | Filter by major |
| `location` | string | Filter by location |
| `is_remote` | boolean | Filter remote opportunities |
| `is_paid` | boolean | Filter paid opportunities |
| `min_salary` | number | Minimum salary |
| `max_salary` | number | Maximum salary |
| `sort_by` | string | Sort field (default: created_at) |
| `sort_order` | string | Sort order (asc/desc) |
| `page` | integer | Page number |
| `per_page` | integer | Items per page (default: 15) |

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "Laravel Developer",
                "description": "...",
                "provider": {
                    "organization_name": "Tech Corp"
                },
                "available_positions": 2,
                "duration_months": 3,
                "application_deadline": "2026-08-01"
            }
        ],
        "total": 25
    },
    "filters_applied": {
        "search": null,
        "major": "IT",
        "location": "Gaza"
    }
}
```

---

### Get Single Opportunity

**Endpoint:** `GET /api/opportunities/{id}`

**Auth Required:** ❌ No

**Success Response (200):**

```json
{
    "opportunity": {
        "id": 1,
        "title": "Laravel Developer",
        "description": "...",
        "requirements": "...",
        "provider": { ... },
        "available_positions": 2,
        "duration_months": 3,
        "status": "open"
    }
}
```

---

### Create Opportunity (Provider - Active Account Required)

**Endpoint:** `POST /api/provider/opportunities`

**Auth Required:** ✅ Yes (Provider with `active` account status only)

**Request Body:**

```json
{
    "title": "Laravel Developer",
    "description": "We need a Laravel developer",
    "requirements": "Laravel, PHP, MySQL",
    "required_major": "IT",
    "required_skills": ["Laravel", "PHP", "MySQL"],
    "available_positions": 2,
    "location": "Gaza",
    "duration_months": 3,
    "start_date": "2026-08-01",
    "end_date": "2026-11-01",
    "application_deadline": "2026-07-31",
    "is_remote": true,
    "is_paid": true,
    "salary": 1000
}
```

**Success Response (201):**

```json
{
    "message": "تم إنشاء الفرصة بنجاح",
    "opportunity": { ... }
}
```

**Error Response (403) - Provider account not active:**

```json
{
    "message": "حسابك قيد المراجعة أو مرفوض. لا يمكنك نشر فرص تدريبية حتى تتم الموافقة على حسابك.",
    "account_status": "pending_review"
}
```

---

### Update Opportunity (Provider)

**Endpoint:** `PUT /api/provider/opportunities/{id}`

**Auth Required:** ✅ Yes (Provider only - owner)

**Request Body:**

```json
{
    "title": "Senior Laravel Developer",
    "available_positions": 5,
    "application_deadline": "2026-08-15"
}
```

**Success Response (200):**

```json
{
    "message": "تم تحديث الفرصة بنجاح",
    "opportunity": { ... }
}
```

---

### Close Opportunity (Provider)

**Endpoint:** `POST /api/provider/opportunities/{id}/close`

**Auth Required:** ✅ Yes (Provider only - owner)

**Success Response (200):**

```json
{
    "message": "تم إغلاق الفرصة بنجاح",
    "opportunity": { ... }
}
```

---

### Reopen Opportunity (Provider)

**Endpoint:** `POST /api/provider/opportunities/{id}/reopen`

**Auth Required:** ✅ Yes (Provider only - owner)

**Success Response (200):**

```json
{
    "message": "تم إعادة فتح الفرصة بنجاح",
    "opportunity": { ... }
}
```

**Error Response (400) - Opportunity not closed:**

```json
{
    "message": "يمكن إعادة فتح الفرصة المغلقة فقط"
}
```

**Error Response (400) - Deadline passed:**

```json
{
    "message": "لا يمكن إعادة فتح الفرصة - الموعد النهائي قد انتهى"
}
```

---

### List Provider's Opportunities

**Endpoint:** `GET /api/provider/opportunities`

**Auth Required:** ✅ Yes (Provider only)

**Success Response (200):**

```json
{
    "opportunities": [
        {
            "id": 1,
            "title": "Laravel Developer",
            "status": "open",
            "applications_count": 5,
            "available_positions": 2
        }
    ]
}
```

---

### Apply for Opportunity (Student)

**Endpoint:** `POST /api/student/opportunities/{id}/apply`

**Auth Required:** ✅ Yes (Student only)

**Headers:**

```
Content-Type: multipart/form-data
```

**Form Data:**

| Field | Type | Required |
|-------|------|----------|
| `cover_letter` | string | ✅ Yes |
| `cv` | file (PDF) | ✅ Yes |

**Success Response (201):**

```json
{
    "message": "تم التقديم بنجاح",
    "application": { ... }
}
```

---

### Track My Applications (Student)

**Endpoint:** `GET /api/student/applications`

**Auth Required:** ✅ Yes (Student only)

**Success Response (200):**

```json
{
    "applications": [
        {
            "id": 1,
            "opportunity": {
                "title": "Laravel Developer",
                "provider": {
                    "user": {
                        "name": "Tech Corp"
                    }
                }
            },
            "status": "pending",
            "applied_at": "2026-07-03T10:00:00Z"
        }
    ]
}
```

---

### Withdraw Application (Student)

**Endpoint:** `POST /api/student/applications/{id}/withdraw`

**Auth Required:** ✅ Yes (Student only - owner)

**Success Response (200):**

```json
{
    "message": "تم الانسحاب بنجاح"
}
```

---

## 📝 Applications

### List Applicants for Opportunity (Provider)

**Endpoint:** `GET /api/provider/opportunities/{id}/applications`

**Auth Required:** ✅ Yes (Provider only - owner)

**Success Response (200):**

```json
{
    "applications": [
        {
            "id": 1,
            "student": {
                "user": {
                    "name": "John Doe",
                    "email": "john@example.com"
                }
            },
            "status": "pending",
            "cover_letter": "...",
            "cv_path": "...",
            "applied_at": "2026-07-03T10:00:00Z"
        }
    ]
}
```

---

### View Applicant Profile (Provider)

**Endpoint:** `GET /api/provider/applicants/{studentId}/profile`

**Auth Required:** ✅ Yes (Provider only - must have received application from this student)

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "student": {
            "id": 1,
            "student_id": "20240001",
            "major": "IT",
            "university": "Islamic University",
            "year_of_study": "3"
        },
        "user": {
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+970591234567"
        },
        "application": {
            "id": 1,
            "opportunity_title": "Laravel Developer",
            "cover_letter": "I am interested in this position...",
            "cv_url": "http://127.0.0.1:8000/storage/cvs/cv.pdf",
            "status": "pending",
            "applied_at": "2026-07-03T10:00:00Z"
        }
    }
}
```

**Error Response (404) - Student didn't apply to this provider:**

```json
{
    "message": "لا توجد بيانات تقديم لهذا الطالب لدى مؤسستك"
}
```

---

### Review Application (Provider)

**Endpoint:** `POST /api/provider/applications/{id}/review`

**Auth Required:** ✅ Yes (Provider only)

**Request Body:**

```json
{
    "status": "accepted",
    "provider_notes": "Welcome to our team!",
    "rejection_reason": null
}
```

**Status Options:** `accepted`, `rejected`

**Success Response (200):**

```json
{
    "message": "تم تحديث حالة التقديم",
    "application": { ... }
}
```

---

## 📊 Weekly Reports

### Submit Weekly Report (Student)

**Endpoint:** `POST /api/student/reports`

**Auth Required:** ✅ Yes (Student only)

**Headers:**

```
Content-Type: multipart/form-data
```

**Form Data:**

| Field | Type | Required |
|-------|------|----------|
| `opportunity_id` | integer | ✅ Yes |
| `report_date` | date | ✅ Yes |
| `week_number` | integer | ✅ Yes |
| `training_hours` | integer | ✅ Yes |
| `completed_tasks` | string | ✅ Yes |
| `challenges` | string | ❌ No |
| `next_week_plan` | string | ❌ No |
| `attachments[]` | file[] | ❌ No |

**Success Response (201):**

```json
{
    "success": true,
    "message": "تم إرسال التقرير بنجاح",
    "report": {
        "id": 1,
        "week_number": 1,
        "status": "submitted",
        "submitted_at": "2026-07-03T10:00:00Z"
    }
}
```

---

### Get Student's Reports

**Endpoint:** `GET /api/student/reports`

**Auth Required:** ✅ Yes (Student only)

**Success Response (200):**

```json
{
    "success": true,
    "reports": [ ... ],
    "count": 5
}
```

---

### Get Supervisor's Students Reports

**Endpoint:** `GET /api/supervisor/reports`

**Auth Required:** ✅ Yes (Supervisor only)

**Success Response (200):**

```json
{
    "success": true,
    "reports": [ ... ],
    "count": 10
}
```

---

### Review Report (Supervisor)

**Endpoint:** `POST /api/supervisor/reports/{id}/review`

**Auth Required:** ✅ Yes (Supervisor only)

**Request Body:**

```json
{
    "status": "approved",
    "supervisor_comments": "Excellent work this week!",
    "grade": 95
}
```

**Status Options:** `approved`, `rejected`, `needs_revision`

**Success Response (200):**

```json
{
    "message": "تم مراجعة التقرير",
    "report": { ... }
}
```

---

### Identify Late Students (Supervisor)

**Endpoint:** `GET /api/supervisor/students/late`

**Auth Required:** ✅ Yes (Supervisor only)

**Success Response (200):**

```json
{
    "success": true,
    "late_students": [
        {
            "student_id": 1,
            "student_number": "20240001",
            "name": "John Doe",
            "email": "john@example.com",
            "major": "IT",
            "last_report_date": "2026-06-14",
            "days_since_last_report": 20,
            "status": "late"
        },
        {
            "student_id": 3,
            "student_number": "20240003",
            "name": "Jane Smith",
            "email": "jane@example.com",
            "major": "IT",
            "last_report_date": null,
            "days_since_last_report": null,
            "status": "never_submitted"
        }
    ],
    "count": 2,
    "deadline": "2026-06-20"
}
```

---

## ⭐ Evaluations

### Provider Evaluation

**Endpoint:** `POST /api/provider/evaluations`

**Auth Required:** ✅ Yes (Provider only)

**Request Body:**

```json
{
    "student_id": 1,
    "opportunity_id": 1,
    "attendance_grade": 90,
    "commitment_grade": 85,
    "technical_skills_grade": 88,
    "teamwork_grade": 92,
    "communication_grade": 87,
    "evaluation_notes": "Outstanding performance",
    "strengths": "Strong technical skills",
    "areas_for_improvement": "Public speaking",
    "is_final": true
}
```

**Grade Range:** 0 - 100

**Success Response (201):**

```json
{
    "message": "Evaluation saved",
    "evaluation": { ... }
}
```

---

### Supervisor Evaluation

**Endpoint:** `POST /api/supervisor/evaluations`

**Auth Required:** ✅ Yes (Supervisor only)

**Request Body:**

```json
{
    "student_id": 1,
    "opportunity_id": 1,
    "technical_skills_grade": 85,
    "commitment_grade": 90,
    "evaluation_notes": "Excellent academic commitment",
    "is_final": true
}
```

**Success Response (201):**

```json
{
    "message": "Evaluation saved",
    "evaluation": { ... }
}
```

---

### Get Student's Evaluations

**Endpoint:** `GET /api/student/evaluations`

**Auth Required:** ✅ Yes (Student only)

**Success Response (200):**

```json
{
    "evaluations": [ ... ]
}
```

---

### Get Student Final Evaluation for Opportunity

**Endpoint:** `GET /api/student/evaluations/opportunity/{opportunityId}/final`

**Auth Required:** ✅ Yes (Student only)

**Success Response (200):**

```json
{
    "final_evaluation": { ... }
}
```

---

### Calculate Final Grade (Admin)

**Endpoint:** `POST /api/admin/students/{student_id}/opportunities/{opportunity_id}/calculate`

**Auth Required:** ✅ Yes (Admin only)

**Success Response (201):**

```json
{
    "success": true,
    "message": "Final grade calculated successfully",
    "record": {
        "student_id": 1,
        "opportunity_id": 1,
        "final_grade": 88.5,
        "status": "completed"
    }
}
```

---

## 🏆 Certificates

### List Student's Certificates

**Endpoint:** `GET /api/student/certificates`

**Auth Required:** ✅ Yes (Student only)

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "opportunity_title": "Laravel Developer",
            "provider_name": "Tech Corp",
            "final_grade": 88.5,
            "status": "completed",
            "certificate_number": "TRN-2026-00001-ABCD",
            "has_certificate": true,
            "issue_date": "2026-07-03"
        }
    ]
}
```

---

### Download Certificate (Student)

**Endpoint:** `GET /api/student/certificates/download`

**Auth Required:** ✅ Yes (Student only)

**Response:** Binary PDF file

**Headers:**

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="certificate_1.pdf"
```

---

### Preview Certificate (Student)

**Endpoint:** `GET /api/student/certificates/preview`

**Auth Required:** ✅ Yes (Student only)

**Response:** PDF displayed in browser

---

### Generate Certificate (Admin)

**Endpoint:** `POST /api/admin/records/{id}/generate-certificate`

**Auth Required:** ✅ Yes (Admin only)

**Success Response (201):**

```json
{
    "success": true,
    "message": "Certificate issued successfully",
    "data": {
        "certificate_number": "TRN-2026-00001-ABCD",
        "file_path": "certificates/TRN-2026-00001-ABCD.pdf",
        "file_url": "http://127.0.0.1:8000/storage/certificates/TRN-2026-00001-ABCD.pdf"
    }
}
```

---

### List All Certificates (Admin)

**Endpoint:** `GET /api/admin/certificates`

**Auth Required:** ✅ Yes (Admin only)

**Success Response (200):**

```json
{
    "success": true,
    "data": [ ... ]
}
```

---

### Download Student Certificate (Admin)

**Endpoint:** `GET /api/admin/students/{studentId}/certificate`

**Auth Required:** ✅ Yes (Admin only)

**Response:** Binary PDF file

---

## 🔔 Notifications

### Get All Notifications

**Endpoint:** `GET /api/notifications`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "notifications": {
        "data": [
            {
                "id": "uuid-here",
                "type": "ApplicationStatusChanged",
                "data": {
                    "message": "Your application has been accepted",
                    "opportunity_id": 1
                },
                "read_at": null,
                "created_at": "2026-07-03T10:00:00Z"
            }
        ]
    },
    "unread_count": 5
}
```

---

### Get Unread Notifications

**Endpoint:** `GET /api/notifications/unread`

**Auth Required:** ✅ Yes

---

### Mark Notification as Read

**Endpoint:** `POST /api/notifications/{id}/read`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "message": "Notification marked as read"
}
```

---

### Mark All as Read

**Endpoint:** `POST /api/notifications/read-all`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "message": "All notifications marked as read"
}
```

---

### Delete Notification

**Endpoint:** `DELETE /api/notifications/{id}`

**Auth Required:** ✅ Yes

---

### Delete All Notifications

**Endpoint:** `DELETE /api/notifications`

**Auth Required:** ✅ Yes

---

## 💬 Messages

### Send Message

**Endpoint:** `POST /api/messages`

**Auth Required:** ✅ Yes

**Request Body:**

```json
{
    "receiver_id": 2,
    "subject": "Inquiry about training",
    "message": "Hello, I would like to know more about...",
    "parent_id": null
}
```

**Success Response (201):**

```json
{
    "success": true,
    "message": "Message sent successfully"
}
```

---

### Get Inbox

**Endpoint:** `GET /api/messages/inbox`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "messages": {
        "data": [
            {
                "id": 1,
                "sender": {
                    "id": 2,
                    "name": "John Doe"
                },
                "subject": "Inquiry",
                "message": "Hello...",
                "is_read": false,
                "created_at": "2026-07-03T10:00:00Z"
            }
        ]
    }
}
```

---

### Get Sent Messages

**Endpoint:** `GET /api/messages/sent`

**Auth Required:** ✅ Yes

---

### Mark Message as Read

**Endpoint:** `POST /api/messages/{id}/read`

**Auth Required:** ✅ Yes (Receiver only)

---

## 🤖 AI Features

Trinova integrates advanced AI capabilities using **Groq LLM** to help students write better internship reports. All AI endpoints are available for students only.

### Language Support

- ✅ **Automatic language detection** (Arabic/English)
- ✅ AI responds in the **same language** as the input
- ✅ Supports mixed-language content

---

### Improve Report (AI)

**Endpoint:** `POST /api/student/ai/reports/improve`

**Auth Required:** ✅ Yes (Student only)

**Description:** Enhances a student's report with professional language and better structure.

**Request Body:**

```json
{
    "content": "تعلمت Laravel اليوم وعملت على database"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "تم تحسين التقرير بنجاح باستخدام الذكاء الاصطناعي.",
    "data": {
        "original_content": "تعلمت Laravel اليوم وعملت على database",
        "improved_content": "خلال هذا اليوم، ركزت على تطوير مهاراتي في إطار عمل Laravel، حيث قمت بتصميم وإدارة قاعدة البيانات بكفاءة. تعلمت كيفية استخدام Eloquent ORM لإدارة العلاقات بين الجداول بشكل فعّال...",
        "detected_language": "arabic",
        "original_word_count": 8,
        "improved_word_count": 45,
        "ai_model": "llama-3.3-70b-versatile"
    }
}
```

**Validation:**
- `content`: required, string, min:10, max:2000

---

### Analyze Report (AI)

**Endpoint:** `POST /api/student/ai/reports/analyze`

**Auth Required:** ✅ Yes (Student only)

**Description:** Analyzes a report and provides quality score, strengths, weaknesses, and suggestions.

**Request Body:**

```json
{
    "content": "تعلمت Laravel اليوم وعملت على database"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "تم تحليل التقرير بنجاح باستخدام الذكاء الاصطناعي.",
    "data": {
        "quality_score": 65,
        "grade": "average",
        "strengths": [
            "ذكر المهام المنجزة بوضوح",
            "الإشارة إلى حل المشكلات",
            "محتوى عملي"
        ],
        "weaknesses": [
            "اللغة غير رسمية",
            "عدم وجود تفاصيل تقنية كافية",
            "غياب التنظيم في فقرات"
        ],
        "improvements": [
            "استخدم مصطلحات تقنية مثل 'تطوير واجهات برمجة التطبيقات'",
            "نظم المحتوى في فقرات واضحة",
            "أضف تفاصيل عن التقنيات المستخدمة"
        ],
        "detailed_feedback": "التقرير يذكر المهام المنجزة بشكل جيد، لكنه يحتاج إلى لغة أكثر احترافية وتنظيم أفضل للمحتوى.",
        "criteria_scores": {
            "content_quality": 70,
            "structure": 50,
            "language": 60,
            "professionalism": 65
        },
        "statistics": {
            "word_count": 14,
            "sentence_count": 1,
            "paragraph_count": 1,
            "character_count": 78,
            "character_count_no_spaces": 65,
            "average_sentence_length": 14,
            "estimated_reading_time_minutes": 1
        },
        "detected_language": "arabic",
        "ai_model": "llama-3.3-70b-versatile"
    }
}
```

**Validation:**
- `content`: required, string, min:10, max:5000

---

### Generate Report from Points (AI)

**Endpoint:** `POST /api/student/ai/reports/generate`

**Auth Required:** ✅ Yes (Student only)

**Description:** Generates a complete professional report from simple bullet points.

**Request Body:**

```json
{
    "points": [
        "تعلمت Laravel",
        "عملت على database وقمت بتصميم الجداول",
        "طورت API للطلاب باستخدام Laravel Sanctum",
        "واجهت مشكلة في الربط بين الجداول وحللتها",
        "تعلمت كيفية استخدام Eloquent ORM"
    ],
    "context": "الأسبوع الأول من التدريب في شركة تقنية"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "تم توليد التقرير بنجاح باستخدام الذكاء الاصطناعي.",
    "data": {
        "input_points": [
            "تعلمت Laravel",
            "عملت على database وقمت بتصميم الجداول",
            "طورت API للطلاب باستخدام Laravel Sanctum"
        ],
        "context": "الأسبوع الأول من التدريب في شركة تقنية",
        "generated_report": "خلال هذا الأسبوع، ركزت على تطوير مهاراتي في إطار عمل Laravel...",
        "detected_language": "arabic",
        "points_count": 5,
        "report_statistics": {
            "word_count": 85,
            "sentence_count": 5,
            "paragraph_count": 1,
            "character_count": 520,
            "character_count_no_spaces": 436,
            "average_sentence_length": 17,
            "estimated_reading_time_minutes": 1
        },
        "ai_model": "llama-3.3-70b-versatile"
    }
}
```

**Validation:**
- `points`: required, array, min:2, max:20
- `points.*`: required, string, min:3, max:200
- `context`: optional, string, max:500

---

### Get Smart Suggestions (AI)

**Endpoint:** `POST /api/student/ai/reports/suggest`

**Auth Required:** ✅ Yes (Student only)

**Description:** Provides intelligent suggestions for what to write in a report based on the student's major and current week.

**Request Body:**

```json
{
    "major": "تقنية المعلومات",
    "current_tasks": "تعلمت Laravel هذا الأسبوع",
    "week_number": 3,
    "language": "arabic"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "تم توليد الاقتراحات بنجاح باستخدام الذكاء الاصطناعي.",
    "data": {
        "suggested_topics": [
            "تطوير واجهات برمجة التطبيقات باستخدام Laravel",
            "إدارة قواعد البيانات والعلاقات",
            "المصادقة والتفويض باستخدام Sanctum",
            "اختبار الوحدات باستخدام PHPUnit",
            "نشر التطبيق على بيئة الإنتاج"
        ],
        "suggested_tasks": [
            "إنشاء نموذج Student مع العلاقات",
            "تطوير API للعمليات CRUD",
            "تطبيق نظام المصادقة",
            "كتابة اختبارات وحدة للـ Controllers",
            "مراجعة كود الزملاء"
        ],
        "suggested_challenges": [
            "فهم العلاقات المعقدة في Eloquent",
            "التعامل مع الأخطاء في الـ APIs",
            "كتابة اختبارات شاملة"
        ],
        "suggested_skills_learned": [
            "تطوير RESTful APIs",
            "إدارة قواعد البيانات العلائقية",
            "المصادقة الآمنة",
            "كتابة كود نظيف وقابل للصيانة"
        ],
        "writing_tips": [
            "استخدم مصطلحات تقنية محددة",
            "اذكر التحديات والحلول بوضوح",
            "وثّق إنجازاتك بأرقام وإحصائيات"
        ],
        "example_bullet_points": [
            "طورت نظام مصادقة كامل باستخدام Laravel Sanctum",
            "أنشأت 5 APIs للعمليات CRUD على الطلاب",
            "حللت مشكلة في العلاقات بين الجداول"
        ],
        "major": "تقنية المعلومات",
        "week_number": 3,
        "detected_language": "arabic",
        "ai_model": "llama-3.3-70b-versatile"
    }
}
```

**Validation:**
- `major`: required, string, max:100
- `current_tasks`: optional, string, max:1000
- `week_number`: optional, integer, min:1, max:52
- `language`: optional, in:arabic,english (auto-detected if not provided)

---

## 🛡️ Admin Management

### List All Providers

**Endpoint:** `GET /api/admin/providers`

**Auth Required:** ✅ Yes (Admin only)

**Success Response (200):**

```json
{
    "success": true,
    "data": [ ... ]
}
```

---

### List Pending Providers

**Endpoint:** `GET /api/admin/providers/pending`

**Auth Required:** ✅ Yes (Admin only)

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Company HR",
            "email": "hr@company.com",
            "phone": "+970591234567",
            "organization_name": "Tech Corp",
            "organization_type": "company",
            "address": "Gaza",
            "city": "Gaza",
            "registered_at": "2026-07-01T10:00:00Z"
        }
    ],
    "meta": {
        "total": 5,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

### Approve Provider

**Endpoint:** `POST /api/admin/providers/{providerId}/approve`

**Auth Required:** ✅ Yes (Admin only)

**Success Response (200):**

```json
{
    "success": true,
    "message": "تمت الموافقة على حساب المزود بنجاح"
}
```

---

### Reject Provider

**Endpoint:** `POST /api/admin/providers/{providerId}/reject`

**Auth Required:** ✅ Yes (Admin only)

**Request Body:**

```json
{
    "reason": "بيانات المؤسسة غير مكتملة. يرجى إعادة التسجيل بمعلومات صحيحة."
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "تم رفض حساب المزود"
}
```

**Validation:**
- `reason`: required, string, max:500

---

### List All Students

**Endpoint:** `GET /api/admin/students`

**Auth Required:** ✅ Yes (Admin only)

---

### List All Supervisors

**Endpoint:** `GET /api/admin/supervisors`

**Auth Required:** ✅ Yes (Admin only)

---

### Assign Supervisor to Student

**Endpoint:** `POST /api/admin/assign-supervisor`

**Auth Required:** ✅ Yes (Admin only)

**Request Body:**

```json
{
    "supervisor_id": 1,
    "student_id": 1
}
```

---

### Approve Internship Record

**Endpoint:** `POST /api/admin/records/{id}/approve`

**Auth Required:** ✅ Yes (Admin only)

---

### Get System Statistics

**Endpoint:** `GET /api/admin/statistics`

**Auth Required:** ✅ Yes (Admin only)

---

### List All Final Evaluations

**Endpoint:** `GET /api/admin/evaluations/final`

**Auth Required:** ✅ Yes (Admin only)

---

### Get Evaluations Statistics

**Endpoint:** `GET /api/admin/evaluations/statistics`

**Auth Required:** ✅ Yes (Admin only)

---

## 🚨 Error Responses

### 401 Unauthorized

```json
{
    "message": "Unauthenticated"
}
```

### 403 Forbidden

```json
{
    "message": "This action is unauthorized"
}
```

### 403 Forbidden - Account Pending Review

```json
{
    "message": "حسابك قيد المراجعة من قبل الإدارة. سيتم إعلامك عند الموافقة.",
    "account_status": "pending_review"
}
```

### 403 Forbidden - Account Rejected

```json
{
    "message": "تم رفض حسابك. بيانات المؤسسة غير مكتملة.",
    "account_status": "rejected",
    "rejection_reason": "بيانات المؤسسة غير مكتملة."
}
```

### 404 Not Found

```json
{
    "message": "Resource not found"
}
```

### 422 Validation Error

```json
{
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### 422 Validation Error - Non-University Email

```json
{
    "message": "The email field must be a valid university email domain.",
    "errors": {
        "email": ["يجب استخدام بريد إلكتروني جامعي رسمي. النطاقات المسموحة: iugaza.edu.ps, alazhar.edu.ps, up.edu.ps"]
    }
}
```

### 500 Server Error

```json
{
    "message": "Server error",
    "error": "Detailed error message (in debug mode only)"
}
```

---

## ⏱️ Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Login | 5 per minute |
| Register | 3 per minute |
| Password Reset | 5 per minute |
| Email Resend | 6 per minute |
| AI Features | 10 per minute |
| General API | 60 per minute |

---

## 🔒 Security Best Practices

1. **Always use HTTPS** in production
2. **Never expose tokens** in URLs or logs
3. **Validate all inputs** on the server side
4. **Use strong passwords** (min 8 characters)
5. **Implement CSRF protection** for web requests
6. **Regular security audits**
7. **Admin review** for new provider accounts
8. **University email validation** for supervisors
9. **Rate limiting** on all sensitive endpoints
10. **XSS and SQL injection protection** via Laravel's built-in features

---

## 🎓 University Email Domains

Supervisors must register with approved university email domains:

| University | Email Domain |
|------------|--------------|
| Islamic University of Gaza | `@iugaza.edu.ps` |
| Al-Azhar University | `@alazhar.edu.ps` |
| University of Palestine | `@up.edu.ps` |
| Al-Aqsa University | `@alaqsa.edu.ps` |
| University of Science & Technology | `@uast.edu.ps` |

To add more domains, edit `config/universities.php`.

---

<div align="center">

**📖 Back to [README.md](../README.md)**

**Made with ❤️ by Trinova Team**

**Powered by Groq AI 🤖**

</div>
