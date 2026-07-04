# 📡 Trinova API Documentation

<div align="center">

**Complete API Reference for Trinova Platform**

![API](https://img.shields.io/badge/API-RESTful-4CAF50?style=flat-square)
![Auth](https://img.shields.io/badge/Auth-Sanctum-FF2D20?style=flat-square)
![Version](https://img.shields.io/badge/Version-1.0-007ACC?style=flat-square)

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

**Request Body:**

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

**Roles and Required Fields:**

| Role | Required Fields |
|------|-----------------|
| `student` | `student_id`, `major`, `university`, `year_of_study` |
| `provider` | `organization_name`, `organization_type`, `address`, `city` |
| `supervisor` | `employee_id`, `department`, `academic_title` |

**Success Response (201):**

```json
{
    "message": "Registration successful. Please verify your email.",
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "student"
    },
    "email_verification_required": true
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
    "message": "Login successful",
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
    "message": "Please verify your email first",
    "email_verification_required": true
}
```

---

### Logout

**Endpoint:** `POST /api/logout`

**Auth Required:** ✅ Yes

**Success Response (200):**

```json
{
    "message": "Logout successful"
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
    "message": "Email verified successfully!",
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
    "message": "Verification link sent again"
}
```

---

### Verification Notice

**Endpoint:** `GET /api/email/verify-notice`

**Auth Required:** ❌ No

**Response (200):**

```json
{
    "message": "Please verify your email. A verification link has been sent to you."
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
    "message": "Password reset link sent to your email"
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
    "message": "Password reset successfully"
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
| `page` | integer | Page number |

**Success Response (200):**

```json
{
    "opportunities": {
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
        "duration_months": 3
    }
}
```

---

### Create Opportunity (Provider)

**Endpoint:** `POST /api/provider/opportunities`

**Auth Required:** ✅ Yes (Provider only)

**Request Body:**

```json
{
    "title": "Laravel Developer",
    "description": "We need a Laravel developer",
    "requirements": "Laravel, PHP, MySQL",
    "required_major": "IT",
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
    "success": true,
    "message": "Opportunity created successfully",
    "opportunity": { ... }
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
    "success": true,
    "message": "Application submitted successfully",
    "application": { ... }
}
```

---

## 📝 Applications

### List Provider's Applications

**Endpoint:** `GET /api/provider/applications`

**Auth Required:** ✅ Yes (Provider only)

**Success Response (200):**

```json
{
    "applications": [
        {
            "id": 1,
            "student": { ... },
            "opportunity": { ... },
            "status": "pending",
            "cover_letter": "...",
            "cv_path": "..."
        }
    ]
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
    "provider_notes": "Welcome to our team!"
}
```

**Status Options:** `accepted`, `rejected`

**Success Response (200):**

```json
{
    "success": true,
    "message": "Application reviewed successfully",
    "application": { ... }
}
```

---

## 📊 Weekly Reports

### Submit Weekly Report (Student)

**Endpoint:** `POST /api/student/reports`

**Auth Required:** ✅ Yes (Student only)

**Request Body:**

```json
{
    "opportunity_id": 1,
    "report_date": "2026-07-03",
    "week_number": 1,
    "training_hours": 30,
    "completed_tasks": "Developed authentication module",
    "challenges": "API integration",
    "next_week_plan": "Payment system"
}
```

**Success Response (201):**

```json
{
    "success": true,
    "message": "Report submitted successfully",
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
    "count": 5,
    "reports": [ ... ]
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
    "count": 10,
    "reports": [ ... ]
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
    "success": true,
    "message": "Report reviewed successfully",
    "report": { ... }
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
| General API | 60 per minute |

---

## 🔒 Security Best Practices

1. **Always use HTTPS** in production
2. **Never expose tokens** in URLs or logs
3. **Validate all inputs** on the server side
4. **Use strong passwords** (min 8 characters)
5. **Implement CSRF protection** for web requests
6. **Regular security audits**

---

<div align="center">

**📖 Back to [README.md](../README.md)**

**Made with ❤️ by Trinova Team**

</div>
