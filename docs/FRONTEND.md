# 🎨 Frontend Integration Guide

<div align="center">

**Complete guide for integrating Trinova API with your frontend application - including AI-powered features & Multilingual Support**

![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript)
![React](https://img.shields.io/badge/React-Supported-61DAFB?style=flat-square&logo=react)
![Vue](https://img.shields.io/badge/Vue-Supported-4FC08D?style=flat-square&logo=vue.js)
![Axios](https://img.shields.io/badge/Axios-Supported-5A29E4?style=flat-square)
![AI](https://img.shields.io/badge/AI-Groq%20LLM-FF6B35?style=flat-square)
![Languages](https://img.shields.io/badge/Languages-Arabic%20%7C%20English-007ACC?style=flat-square)

</div>

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Base URL](#-base-url)
- [Authentication Flow](#-authentication-flow)
- [Making Authenticated Requests](#-making-authenticated-requests)
- [Multilingual Support](#-multilingual-support)
- [Example: Get Opportunities](#-example-get-opportunities)
- [Example: Apply for Opportunity](#-example-apply-for-opportunity)
- [Example: Submit Weekly Report](#-example-submit-weekly-report)
- [Example: Download Certificate](#-example-download-certificate)
- [Example: Get Notifications](#-example-get-notifications)
- [Example: Send Message](#-example-send-message)
- [AI-Powered Features](#-ai-powered-features)
- [Admin Provider Management](#-admin-provider-management)
- [Admin User Management](#-admin-user-management)
- [React Integration](#-react-integration)
- [Vue.js Integration](#-vuejs-integration)
- [CORS Configuration](#-cors-configuration)
- [Error Handling](#-error-handling)
- [Best Practices](#-best-practices)

---

## 📋 Overview

This guide helps frontend developers integrate with the Trinova API using JavaScript, React, or Vue.js.

### What you'll learn:

- 🔐 How to authenticate users (with admin review for providers)
- 📡 How to make API requests
- 🌍 How to implement multilingual support (Arabic/English)
- 💼 How to handle opportunities and applications
- 📊 How to submit reports and evaluations
- 🏆 How to download certificates
- 🔔 How to handle notifications
- 💬 How to send messages
- 🤖 How to use AI-powered report features
- 🛡️ How to manage provider approvals (admin)
- 👨‍💼 How to manage users (admin)

---

## 🌐 Base URL

### Development

```javascript
const API_BASE_URL = 'http://127.0.0.1:8000/api';
```

### Production

```javascript
const API_BASE_URL = 'https://your-domain.com/api';
```

---

## 🔐 Authentication Flow

### Step 1: Register

```javascript
async function register(userData) {
    const response = await fetch(`${API_BASE_URL}/register`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(userData)
    });
    
    const data = await response.json();
    
    if (response.ok) {
        // Save token
        localStorage.setItem('token', data.token);
        
        // ✅ Check account status
        if (data.account_status === 'pending_review') {
            alert('تم التسجيل بنجاح. حسابك قيد المراجعة من قبل الإدارة.');
            return { success: true, pendingReview: true, data };
        }
        
        return { success: true, data };
    }
    
    return { success: false, error: data.message };
}

// Usage - Student Registration
register({
    name: 'John Doe',
    email: 'john@example.com',
    password: 'password123',
    password_confirmation: 'password123',
    role: 'student',
    student_id: '20240001',
    major: 'IT',
    university: 'Test University',
    year_of_study: '3'
});

// Usage - Provider Registration (requires admin approval)
register({
    name: 'Company HR',
    email: 'hr@company.com',
    password: 'password123',
    password_confirmation: 'password123',
    role: 'provider',
    organization_name: 'Tech Corp',
    organization_type: 'company',
    address: 'Gaza City',
    city: 'Gaza'
});

// Usage - Supervisor Registration (university email required)
register({
    name: 'Dr. Ahmed',
    email: 'ahmed@iugaza.edu.ps', // Must be university email
    password: 'password123',
    password_confirmation: 'password123',
    role: 'supervisor',
    employee_id: 'EMP001',
    department: 'Computer Science',
    academic_title: 'professor'
});
```

---

### Step 2: Verify Email

After registration, the user must click the verification link sent to their email.

**Check the email log:**

```powershell
Get-Content storage/logs/laravel.log -Tail 100 | Select-String "email/verify"
```

---

### Step 3: Login

```javascript
async function login(email, password) {
    const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (response.ok) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return { success: true, data };
    }
    
    // ✅ Handle different error scenarios
    if (response.status === 403) {
        if (data.email_verification_required) {
            return { 
                success: false, 
                error: 'يرجى التحقق من بريدك الإلكتروني أولاً',
                needsVerification: true 
            };
        }
        
        if (data.account_status === 'pending_review') {
            return { 
                success: false, 
                error: 'حسابك قيد المراجعة من قبل الإدارة',
                pendingReview: true 
            };
        }
        
        if (data.account_status === 'rejected') {
            return { 
                success: false, 
                error: `تم رفض حسابك: ${data.rejection_reason}`,
                rejected: true 
            };
        }

        if (data.account_status === 'suspended') {
            return { 
                success: false, 
                error: 'حسابك معلق. يرجى التواصل مع الإدارة.',
                suspended: true 
            };
        }
    }
    
    return { success: false, error: data.message };
}

// Usage
const result = await login('john@example.com', 'password123');

if (result.pendingReview) {
    showPendingReviewPage();
} else if (result.needsVerification) {
    showVerificationPage();
} else if (result.success) {
    window.location.href = '/dashboard';
}
```

---

### Step 4: Logout

```javascript
async function logout() {
    const token = localStorage.getItem('token');
    
    const response = await fetch(`${API_BASE_URL}/logout`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    return await response.json();
}
```

---

## 📡 Making Authenticated Requests

### Helper Function

```javascript
async function apiRequest(endpoint, options = {}) {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    const defaultHeaders = {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    };

    // ✅ Add language header based on user preference
    if (user.preferred_language) {
        defaultHeaders['X-Language'] = user.preferred_language;
    }
    
    // Don't set Content-Type for FormData
    if (!(options.body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
    }
    
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers
        }
    });
    
    // Handle 401 Unauthorized
    if (response.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
        return;
    }
    
    // Handle 403 Forbidden - Account Issues
    if (response.status === 403) {
        const data = await response.json();
        
        if (data.account_status === 'pending_review') {
            alert('حسابك قيد المراجعة من قبل الإدارة');
            window.location.href = '/pending-review';
            return;
        }
        
        if (data.account_status === 'rejected') {
            alert(`تم رفض حسابك: ${data.rejection_reason}`);
            window.location.href = '/account-rejected';
            return;
        }

        if (data.account_status === 'suspended') {
            alert('حسابك معلق. يرجى التواصل مع الإدارة.');
            window.location.href = '/account-suspended';
            return;
        }
    }
    
    return response;
}
```

---

## 🌍 Multilingual Support

Trinova supports **dynamic language switching** in the frontend. The API automatically responds in the user's preferred language.

### How It Works

The API detects language in this priority order:

| Priority | Source | Example |
|----------|--------|---------|
| 1️⃣ | Query Parameter | `?lang=ar` |
| 2️⃣ | Custom Header | `X-Language: en` |
| 3️⃣ | User Preference | `preferred_language` in database |
| 4️⃣ | Accept-Language Header | `Accept-Language: ar,en;q=0.9` |
| 5️⃣ | Default from .env | `APP_LOCALE=ar` |

### Change User Language

```javascript
async function changeUserLanguage(language) {
    const response = await apiRequest('/user/language', {
        method: 'POST',
        body: JSON.stringify({ language })
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Update local storage
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        user.preferred_language = language;
        localStorage.setItem('user', JSON.stringify(user));
        
        // Reload page to apply new language
        window.location.reload();
        
        return { success: true, language: data.language };
    }
    
    return { success: false, error: data.message };
}

// Usage - Switch to English
await changeUserLanguage('en');

// Usage - Switch to Arabic
await changeUserLanguage('ar');
```

### Language Selector Component (React)

```javascript
// LanguageSelector.jsx
import { useState } from 'react';
import { useAuth } from './AuthContext';

function LanguageSelector() {
    const { user, updateUser } = useAuth();
    const [currentLang, setCurrentLang] = useState(user?.preferred_language || 'ar');
    const [loading, setLoading] = useState(false);

    const handleLanguageChange = async (lang) => {
        setLoading(true);
        try {
            const response = await fetch(`${API_BASE_URL}/user/language`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({ language: lang })
            });

            const data = await response.json();
            
            if (data.success) {
                setCurrentLang(lang);
                updateUser({ ...user, preferred_language: lang });
                
                // Apply direction change
                document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
                document.documentElement.lang = lang;
                
                // Reload to apply translations
                setTimeout(() => window.location.reload(), 500);
            }
        } catch (error) {
            console.error('Failed to change language:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="language-selector">
            <button 
                onClick={() => handleLanguageChange('ar')}
                className={currentLang === 'ar' ? 'active' : ''}
                disabled={loading}
            >
                العربية 🇸🇦
            </button>
            <button 
                onClick={() => handleLanguageChange('en')}
                className={currentLang === 'en' ? 'active' : ''}
                disabled={loading}
            >
                English 🇬🇧
            </button>
        </div>
    );
}

export default LanguageSelector;
```

### Apply Language Direction

```javascript
// Apply RTL/LTR based on language
function applyLanguageDirection(language) {
    const direction = language === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.dir = direction;
    document.documentElement.lang = language;
    
    // Update body class
    document.body.classList.remove('ltr', 'rtl');
    document.body.classList.add(direction);
}

// Call on app initialization
const user = JSON.parse(localStorage.getItem('user') || '{}');
if (user.preferred_language) {
    applyLanguageDirection(user.preferred_language);
}
```

### CSS for RTL Support

```css
/* styles.css */

/* Base styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    direction: ltr;
}

/* Arabic-specific styles */
body.rtl {
    direction: rtl;
    text-align: right;
    font-family: 'Cairo', 'Tajawal', 'Segoe UI', sans-serif;
}

/* Navigation */
.nav {
    display: flex;
    gap: 1rem;
}

body.rtl .nav {
    flex-direction: row-reverse;
}

/* Forms */
input, textarea, select {
    direction: inherit;
    text-align: inherit;
}

/* Buttons */
.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
}

/* Cards */
.card {
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
}

body.rtl .card {
    text-align: right;
}
```

### Request with Specific Language

```javascript
// Request with X-Language header
async function getProfileWithLanguage(language) {
    const response = await fetch(`${API_BASE_URL}/profile`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'X-Language': language,
            'Accept': 'application/json'
        }
    });
    
    return await response.json();
}

// Request with query parameter
async function getOpportunitiesWithLanguage(language) {
    const response = await fetch(`${API_BASE_URL}/opportunities?lang=${language}`, {
        headers: {
            'Accept': 'application/json'
        }
    });
    
    return await response.json();
}
```

---

## 💼 Example: Get Opportunities

```javascript
async function getOpportunities(filters = {}) {
    const queryParams = new URLSearchParams(filters).toString();
    const endpoint = queryParams ? `/opportunities?${queryParams}` : '/opportunities';
    
    const response = await apiRequest(endpoint);
    const data = await response.json();
    
    return data.data || data.opportunities?.data || [];
}

// Usage
const opportunities = await getOpportunities({
    major: 'IT',
    is_remote: true,
    page: 1,
    per_page: 15
});

console.log(opportunities);
```

---

## 📝 Example: Apply for Opportunity

```javascript
async function applyForOpportunity(opportunityId, coverLetter, cvFile) {
    const formData = new FormData();
    formData.append('cover_letter', coverLetter);
    formData.append('cv', cvFile);
    
    const response = await apiRequest(
        `/student/opportunities/${opportunityId}/apply`,
        {
            method: 'POST',
            body: formData
        }
    );
    
    return await response.json();
}

// Usage
const fileInput = document.querySelector('input[type="file"]');
const cvFile = fileInput.files[0];

const result = await applyForOpportunity(
    1,
    'I am very interested in this opportunity...',
    cvFile
);

if (result.message) {
    alert('Application submitted successfully!');
}
```

---

## 📊 Example: Submit Weekly Report

```javascript
async function submitReport(reportData) {
    const response = await apiRequest('/student/reports', {
        method: 'POST',
        body: JSON.stringify(reportData)
    });
    
    return await response.json();
}

// Usage
submitReport({
    opportunity_id: 1,
    report_date: '2026-07-03',
    week_number: 1,
    training_hours: 30,
    completed_tasks: 'Developed authentication module',
    challenges: 'API integration',
    next_week_plan: 'Payment system'
});
```

---

## 🏆 Example: Download Certificate

```javascript
async function downloadCertificate() {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    const response = await fetch(
        `${API_BASE_URL}/student/certificates/download`,
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'X-Language': user.preferred_language || 'ar'
            }
        }
    );
    
    if (!response.ok) {
        throw new Error('Failed to download certificate');
    }
    
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'certificate.pdf';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Usage
downloadCertificate();
```

---

## 🔔 Example: Get Notifications

```javascript
async function getNotifications() {
    const response = await apiRequest('/notifications');
    const data = await response.json();
    
    return {
        notifications: data.notifications.data,
        unreadCount: data.unread_count
    };
}

// Usage
const { notifications, unreadCount } = await getNotifications();
console.log(`You have ${unreadCount} unread notifications`);
```

---

### Mark Notification as Read

```javascript
async function markAsRead(notificationId) {
    const response = await apiRequest(`/notifications/${notificationId}/read`, {
        method: 'POST'
    });
    
    return await response.json();
}

// Usage
markAsRead('uuid-here');
```

---

### Mark All as Read

```javascript
async function markAllAsRead() {
    const response = await apiRequest('/notifications/read-all', {
        method: 'POST'
    });
    
    return await response.json();
}
```

---

## 💬 Example: Send Message

```javascript
async function sendMessage(receiverId, subject, message, parentId = null) {
    const response = await apiRequest('/messages', {
        method: 'POST',
        body: JSON.stringify({
            receiver_id: receiverId,
            subject,
            message,
            parent_id: parentId
        })
    });
    
    return await response.json();
}

// Usage
sendMessage(
    2,
    'Inquiry about training',
    'Hello, I would like to know more about the opportunity...'
);
```

---

### Get Inbox

```javascript
async function getInbox() {
    const response = await apiRequest('/messages/inbox');
    const data = await response.json();
    
    return data.messages.data;
}
```

---

## 🤖 AI-Powered Features

Trinova provides AI-powered features to help students write better internship reports using **Groq LLM**.

### Feature 1: Improve Report

Enhances a student's report with professional language and better structure.

```javascript
async function improveReport(content) {
    const response = await apiRequest('/student/ai/reports/improve', {
        method: 'POST',
        body: JSON.stringify({ content })
    });
    
    return await response.json();
}

// Usage
const result = await improveReport('تعلمت Laravel اليوم وعملت على database');

if (result.success) {
    console.log('Original:', result.data.original_content);
    console.log('Improved:', result.data.improved_content);
    console.log('Language:', result.data.detected_language);
    console.log('Words before:', result.data.original_word_count);
    console.log('Words after:', result.data.improved_word_count);
}
```

---

### Feature 2: Analyze Report

Analyzes a report and provides quality score, strengths, weaknesses, and suggestions.

```javascript
async function analyzeReport(content) {
    const response = await apiRequest('/student/ai/reports/analyze', {
        method: 'POST',
        body: JSON.stringify({ content })
    });
    
    return await response.json();
}

// Usage
const result = await analyzeReport('تعلمت Laravel اليوم وعملت على database');

if (result.success) {
    console.log('Quality Score:', result.data.quality_score);
    console.log('Grade:', result.data.grade);
    console.log('Strengths:', result.data.strengths);
    console.log('Weaknesses:', result.data.weaknesses);
    console.log('Improvements:', result.data.improvements);
    console.log('Feedback:', result.data.detailed_feedback);
    
    // Display statistics
    console.log('Word Count:', result.data.statistics.word_count);
    console.log('Reading Time:', result.data.statistics.estimated_reading_time_minutes, 'minutes');
}
```

---

### Feature 3: Generate Report from Points

Generates a complete professional report from simple bullet points.

```javascript
async function generateReport(points, context = '') {
    const response = await apiRequest('/student/ai/reports/generate', {
        method: 'POST',
        body: JSON.stringify({ points, context })
    });
    
    return await response.json();
}

// Usage
const result = await generateReport(
    [
        'تعلمت Laravel',
        'عملت على database وقمت بتصميم الجداول',
        'طورت API للطلاب باستخدام Laravel Sanctum',
        'واجهت مشكلة في الربط بين الجداول وحللتها'
    ],
    'الأسبوع الأول من التدريب في شركة تقنية'
);

if (result.success) {
    console.log('Generated Report:', result.data.generated_report);
    console.log('Points Count:', result.data.points_count);
    console.log('Report Stats:', result.data.report_statistics);
}
```

---

### Feature 4: Get Smart Suggestions

Provides intelligent suggestions for what to write in a report based on the student's major and current week.

```javascript
async function getSuggestions(major, weekNumber = 1, currentTasks = '') {
    const response = await apiRequest('/student/ai/reports/suggest', {
        method: 'POST',
        body: JSON.stringify({
            major,
            week_number: weekNumber,
            current_tasks: currentTasks
        })
    });
    
    return await response.json();
}

// Usage
const result = await getSuggestions('تقنية المعلومات', 3, 'تعلمت Laravel');

if (result.success) {
    console.log('Suggested Topics:', result.data.suggested_topics);
    console.log('Suggested Tasks:', result.data.suggested_tasks);
    console.log('Challenges:', result.data.suggested_challenges);
    console.log('Skills Learned:', result.data.suggested_skills_learned);
    console.log('Writing Tips:', result.data.writing_tips);
    console.log('Example Points:', result.data.example_bullet_points);
}
```

---

### Complete AI Workflow Example

```javascript
// Complete workflow: Get suggestions → Generate report → Improve → Analyze
async function completeAIWorkflow(major, weekNumber) {
    try {
        // Step 1: Get suggestions
        const suggestions = await getSuggestions(major, weekNumber);
        if (!suggestions.success) throw new Error('Failed to get suggestions');
        
        // Step 2: Generate report from example points
        const generated = await generateReport(
            suggestions.data.example_bullet_points,
            `Week ${weekNumber} of internship`
        );
        if (!generated.success) throw new Error('Failed to generate report');
        
        // Step 3: Improve the report
        const improved = await improveReport(generated.data.generated_report);
        if (!improved.success) throw new Error('Failed to improve report');
        
        // Step 4: Analyze the final report
        const analyzed = await analyzeReport(improved.data.improved_content);
        if (!analyzed.success) throw new Error('Failed to analyze report');
        
        return {
            suggestions: suggestions.data,
            generated: generated.data,
            improved: improved.data,
            analysis: analyzed.data
        };
        
    } catch (error) {
        console.error('AI Workflow Error:', error);
        return null;
    }
}

// Usage
const workflowResult = await completeAIWorkflow('Computer Science', 3);
console.log('Complete Workflow Result:', workflowResult);
```

---

## 🛡️ Admin Provider Management

### Get Pending Providers (Admin Only)

```javascript
async function getPendingProviders() {
    const response = await apiRequest('/admin/providers/pending');
    return await response.json();
}

// Usage
const result = await getPendingProviders();

if (result.success) {
    console.log('Pending Providers:', result.data);
    console.log('Total:', result.meta.total);
}
```

---

### Approve Provider (Admin Only)

```javascript
async function approveProvider(providerId) {
    const response = await apiRequest(`/admin/providers/${providerId}/approve`, {
        method: 'POST'
    });
    
    return await response.json();
}

// Usage
const result = await approveProvider(5);

if (result.success) {
    alert('Provider approved successfully!');
    refreshPendingProviders();
}
```

---

### Reject Provider (Admin Only)

```javascript
async function rejectProvider(providerId, reason) {
    const response = await apiRequest(`/admin/providers/${providerId}/reject`, {
        method: 'POST',
        body: JSON.stringify({ reason })
    });
    
    return await response.json();
}

// Usage
const result = await rejectProvider(
    5,
    'بيانات المؤسسة غير مكتملة. يرجى إعادة التسجيل بمعلومات صحيحة.'
);

if (result.success) {
    alert('Provider rejected');
    refreshPendingProviders();
}
```

---

### Admin Dashboard Component Example

```javascript
// AdminProviderReview.jsx (React)
import { useState, useEffect } from 'react';

function AdminProviderReview() {
    const [providers, setProviders] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        loadPendingProviders();
    }, []);
    
    async function loadPendingProviders() {
        const result = await getPendingProviders();
        if (result.success) {
            setProviders(result.data);
        }
        setLoading(false);
    }
    
    async function handleApprove(providerId) {
        if (confirm('Are you sure you want to approve this provider?')) {
            const result = await approveProvider(providerId);
            if (result.success) {
                loadPendingProviders();
            }
        }
    }
    
    async function handleReject(providerId) {
        const reason = prompt('Enter rejection reason:');
        if (reason) {
            const result = await rejectProvider(providerId, reason);
            if (result.success) {
                loadPendingProviders();
            }
        }
    }
    
    if (loading) return <div>Loading...</div>;
    
    return (
        <div>
            <h1>Pending Provider Approvals</h1>
            {providers.length === 0 ? (
                <p>No pending providers</p>
            ) : (
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Organization</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {providers.map(provider => (
                            <tr key={provider.id}>
                                <td>{provider.name}</td>
                                <td>{provider.email}</td>
                                <td>{provider.organization_name}</td>
                                <td>
                                    <button onClick={() => handleApprove(provider.id)}>
                                        ✅ Approve
                                    </button>
                                    <button onClick={() => handleReject(provider.id)}>
                                        ❌ Reject
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
```

---

## 👨‍💼 Admin User Management

Admins have full control over all users in the system through these powerful endpoints.

### List All Users (Admin Only)

```javascript
async function getAllUsers(filters = {}) {
    const queryParams = new URLSearchParams(filters).toString();
    const endpoint = queryParams ? `/admin/users?${queryParams}` : '/admin/users';
    
    const response = await apiRequest(endpoint);
    return await response.json();
}

// Usage - Get all users
const result = await getAllUsers();

// Usage - Filter by role
const students = await getAllUsers({ role: 'student' });

// Usage - Filter by status
const activeUsers = await getAllUsers({ status: 'active' });

// Usage - Search by name or email
const searchResult = await getAllUsers({ search: 'john' });

// Usage - Combined filters
const filteredUsers = await getAllUsers({
    role: 'provider',
    status: 'active',
    search: 'tech',
    page: 1,
    per_page: 20
});
```

---

### View User Details (Admin Only)

```javascript
async function getUserDetails(userId) {
    const response = await apiRequest(`/admin/users/${userId}`);
    return await response.json();
}

// Usage
const result = await getUserDetails(5);

if (result.success) {
    console.log('User:', result.data);
    console.log('Role:', result.data.role);
    console.log('Status:', result.data.account_status);
    console.log('Language:', result.data.preferred_language);
}
```

---

### Create New User (Admin Only)

```javascript
async function createUser(userData) {
    const response = await apiRequest('/admin/users', {
        method: 'POST',
        body: JSON.stringify(userData)
    });
    
    return await response.json();
}

// Usage - Create Student
const student = await createUser({
    name: 'Ahmed Mohamed',
    email: 'ahmed@university.edu',
    password: 'password123',
    phone: '0591234567',
    role: 'student',
    student_id: '20240999',
    major: 'IT',
    university: 'Islamic University',
    year_of_study: '3'
});

// Usage - Create Provider
const provider = await createUser({
    name: 'Tech Corp',
    email: 'hr@techcorp.com',
    password: 'password123',
    role: 'provider',
    organization_name: 'Tech Corporation',
    organization_type: 'company',
    address: 'Gaza City',
    city: 'Gaza',
    country: 'Palestine',
    account_status: 'active'
});

// Usage - Create Supervisor
const supervisor = await createUser({
    name: 'Dr. Sarah Ahmed',
    email: 'sarah@iugaza.edu.ps',
    password: 'password123',
    role: 'supervisor',
    employee_id: 'EMP999',
    department: 'Computer Science',
    academic_title: 'professor'
});

// Usage - Create Admin
const admin = await createUser({
    name: 'System Admin',
    email: 'admin@trinova.com',
    password: 'StrongPassword123!',
    role: 'admin'
});
```

---

### Update User (Admin Only)

```javascript
async function updateUser(userId, userData) {
    const response = await apiRequest(`/admin/users/${userId}`, {
        method: 'PUT',
        body: JSON.stringify(userData)
    });
    
    return await response.json();
}

// Usage
const result = await updateUser(5, {
    name: 'Updated Name',
    email: 'updated@example.com',
    phone: '0591234567'
});
```

---

### Delete User (Admin Only)

```javascript
async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return { success: false, error: 'Cancelled' };
    }

    const response = await apiRequest(`/admin/users/${userId}`, {
        method: 'DELETE'
    });
    
    return await response.json();
}

// Usage
const result = await deleteUser(5);

if (result.success) {
    alert('User deleted successfully');
    refreshUsersList();
}
```

---

### Suspend User (Admin Only)

```javascript
async function suspendUser(userId) {
    const response = await apiRequest(`/admin/users/${userId}/suspend`, {
        method: 'POST'
    });
    
    return await response.json();
}

// Usage
const result = await suspendUser(5);

if (result.success) {
    alert('User suspended successfully');
    refreshUsersList();
}
```

---

### Activate User (Admin Only)

```javascript
async function activateUser(userId) {
    const response = await apiRequest(`/admin/users/${userId}/activate`, {
        method: 'POST'
    });
    
    return await response.json();
}

// Usage
const result = await activateUser(5);

if (result.success) {
    alert('User activated successfully');
    refreshUsersList();
}
```

---

### Reset User Password (Admin Only)

```javascript
async function resetUserPassword(userId, newPassword) {
    const response = await apiRequest(`/admin/users/${userId}/reset-password`, {
        method: 'POST',
        body: JSON.stringify({
            password: newPassword,
            password_confirmation: newPassword
        })
    });
    
    return await response.json();
}

// Usage
const result = await resetUserPassword(5, 'newpassword123');

if (result.success) {
    alert('Password reset successfully. All user tokens have been invalidated.');
}
```

---

### Admin User Management Dashboard (React)

```javascript
// AdminUserManagement.jsx
import { useState, useEffect } from 'react';

function AdminUserManagement() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        role: '',
        status: '',
        search: ''
    });
    const [showCreateModal, setShowCreateModal] = useState(false);

    useEffect(() => {
        loadUsers();
    }, [filters]);

    async function loadUsers() {
        setLoading(true);
        const result = await getAllUsers(filters);
        if (result.success) {
            setUsers(result.data.data);
        }
        setLoading(false);
    }

    async function handleSuspend(userId) {
        if (confirm('Are you sure you want to suspend this user?')) {
            const result = await suspendUser(userId);
            if (result.success) {
                loadUsers();
            }
        }
    }

    async function handleActivate(userId) {
        const result = await activateUser(userId);
        if (result.success) {
            loadUsers();
        }
    }

    async function handleDelete(userId) {
        const result = await deleteUser(userId);
        if (result.success) {
            loadUsers();
        }
    }

    async function handleResetPassword(userId) {
        const newPassword = prompt('Enter new password (min 8 characters):');
        if (newPassword && newPassword.length >= 8) {
            const result = await resetUserPassword(userId, newPassword);
            if (result.success) {
                alert('Password reset successfully');
            }
        }
    }

    if (loading) return <div>Loading...</div>;

    return (
        <div className="admin-user-management">
            <div className="header">
                <h1>User Management</h1>
                <button onClick={() => setShowCreateModal(true)}>
                    ➕ Create New User
                </button>
            </div>

            {/* Filters */}
            <div className="filters">
                <select 
                    value={filters.role} 
                    onChange={(e) => setFilters({...filters, role: e.target.value})}
                >
                    <option value="">All Roles</option>
                    <option value="student">Student</option>
                    <option value="provider">Provider</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="admin">Admin</option>
                </select>

                <select 
                    value={filters.status} 
                    onChange={(e) => setFilters({...filters, status: e.target.value})}
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="suspended">Suspended</option>
                    <option value="rejected">Rejected</option>
                </select>

                <input 
                    type="text"
                    placeholder="Search by name or email..."
                    value={filters.search}
                    onChange={(e) => setFilters({...filters, search: e.target.value})}
                />
            </div>

            {/* Users Table */}
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Language</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {users.map(user => (
                        <tr key={user.id}>
                            <td>{user.id}</td>
                            <td>{user.name}</td>
                            <td>{user.email}</td>
                            <td>
                                <span className={`badge badge-${user.role}`}>
                                    {user.role}
                                </span>
                            </td>
                            <td>
                                <span className={`status status-${user.account_status}`}>
                                    {user.account_status}
                                </span>
                            </td>
                            <td>{user.preferred_language || 'ar'}</td>
                            <td className="actions">
                                <button onClick={() => handleSuspend(user.id)}>
                                    ⏸️ Suspend
                                </button>
                                <button onClick={() => handleActivate(user.id)}>
                                    ▶️ Activate
                                </button>
                                <button onClick={() => handleResetPassword(user.id)}>
                                    🔑 Reset Password
                                </button>
                                <button onClick={() => handleDelete(user.id)}>
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default AdminUserManagement;
```

---

## 🎯 React Integration

### Auth Context

```javascript
// AuthContext.js
import { createContext, useState, useEffect, useContext } from 'react';

const AuthContext = createContext();

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [token, setToken] = useState(localStorage.getItem('token'));
    const [loading, setLoading] = useState(true);
    const [accountStatus, setAccountStatus] = useState(null);
    const [language, setLanguage] = useState('ar');
    
    useEffect(() => {
        if (token) {
            fetchProfile();
        } else {
            setLoading(false);
        }
    }, [token]);

    // Apply language direction on mount
    useEffect(() => {
        const savedUser = JSON.parse(localStorage.getItem('user') || '{}');
        const lang = savedUser.preferred_language || 'ar';
        setLanguage(lang);
        applyLanguageDirection(lang);
    }, []);
    
    async function fetchProfile() {
        try {
            const response = await fetch(`${API_BASE_URL}/profile`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'X-Language': language
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                setUser(data.user);
                setAccountStatus(data.user.account_status);
                
                // Update language if changed
                if (data.user.preferred_language) {
                    setLanguage(data.user.preferred_language);
                    applyLanguageDirection(data.user.preferred_language);
                }
            } else {
                logout();
            }
        } catch (error) {
            console.error('Failed to fetch profile:', error);
            logout();
        } finally {
            setLoading(false);
        }
    }
    
    async function login(email, password) {
        const response = await fetch(`${API_BASE_URL}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            setToken(data.token);
            setUser(data.user);
            setAccountStatus(data.user.account_status);
            
            // Set language
            const lang = data.user.preferred_language || 'ar';
            setLanguage(lang);
            applyLanguageDirection(lang);
            
            return { success: true };
        }
        
        return { 
            success: false, 
            error: data.message,
            accountStatus: data.account_status 
        };
    }

    async function changeLanguage(newLanguage) {
        try {
            const response = await fetch(`${API_BASE_URL}/user/language`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ language: newLanguage })
            });

            const data = await response.json();
            
            if (data.success) {
                setLanguage(newLanguage);
                applyLanguageDirection(newLanguage);
                
                // Update user in localStorage
                const updatedUser = { ...user, preferred_language: newLanguage };
                setUser(updatedUser);
                localStorage.setItem('user', JSON.stringify(updatedUser));
                
                return { success: true };
            }
            
            return { success: false, error: data.message };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }
    
    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setToken(null);
        setUser(null);
        setAccountStatus(null);
    }

    function applyLanguageDirection(lang) {
        const direction = lang === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.dir = direction;
        document.documentElement.lang = lang;
        document.body.classList.remove('ltr', 'rtl');
        document.body.classList.add(direction);
    }
    
    return (
        <AuthContext.Provider value={{ 
            user, 
            token, 
            loading, 
            accountStatus,
            language,
            login, 
            logout,
            changeLanguage,
            fetchProfile 
        }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
```

---

### Using the Context

```javascript
// Dashboard.js
import { useAuth } from './AuthContext';
import LanguageSelector from './LanguageSelector';

function Dashboard() {
    const { user, logout, loading, accountStatus, language, changeLanguage } = useAuth();
    
    if (loading) {
        return <div>Loading...</div>;
    }
    
    if (!user) {
        return <div>Please login</div>;
    }
    
    // Handle pending review status
    if (accountStatus === 'pending_review') {
        return (
            <div>
                <h1>Account Under Review</h1>
                <p>Your account is being reviewed by the admin.</p>
                <p>You will receive an email notification once approved.</p>
                <button onClick={logout}>Logout</button>
            </div>
        );
    }

    // Handle suspended status
    if (accountStatus === 'suspended') {
        return (
            <div>
                <h1>Account Suspended</h1>
                <p>Your account has been suspended. Please contact administration.</p>
                <button onClick={logout}>Logout</button>
            </div>
        );
    }
    
    return (
        <div>
            <header>
                <LanguageSelector />
                <button onClick={logout}>Logout</button>
            </header>
            
            <h1>Welcome, {user.name}!</h1>
            <p>Role: {user.role}</p>
            <p>Email: {user.email}</p>
            <p>Account Status: {accountStatus}</p>
            <p>Language: {language === 'ar' ? 'العربية' : 'English'}</p>
        </div>
    );
}
```

---

### Protected Route Component

```javascript
// ProtectedRoute.js
import { Navigate } from 'react-router-dom';
import { useAuth } from './AuthContext';

function ProtectedRoute({ children, requiredRole }) {
    const { user, loading, accountStatus } = useAuth();
    
    if (loading) {
        return <div>Loading...</div>;
    }
    
    if (!user) {
        return <Navigate to="/login" replace />;
    }
    
    // Check account status
    if (accountStatus === 'pending_review') {
        return <Navigate to="/pending-review" replace />;
    }
    
    if (accountStatus === 'rejected') {
        return <Navigate to="/account-rejected" replace />;
    }

    if (accountStatus === 'suspended') {
        return <Navigate to="/account-suspended" replace />;
    }
    
    // Check role
    if (requiredRole && user.role !== requiredRole) {
        return <Navigate to="/unauthorized" replace />;
    }
    
    return children;
}

export default ProtectedRoute;
```

---

### AI Report Assistant Component (React)

```javascript
// AIReportAssistant.jsx
import { useState } from 'react';

function AIReportAssistant() {
    const [content, setContent] = useState('');
    const [points, setPoints] = useState(['', '']);
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [mode, setMode] = useState('improve');
    
    async function handleImprove() {
        setLoading(true);
        const response = await improveReport(content);
        setResult(response);
        setLoading(false);
    }
    
    async function handleAnalyze() {
        setLoading(true);
        const response = await analyzeReport(content);
        setResult(response);
        setLoading(false);
    }
    
    async function handleGenerate() {
        setLoading(true);
        const filteredPoints = points.filter(p => p.trim());
        const response = await generateReport(filteredPoints);
        setResult(response);
        setLoading(false);
    }
    
    async function handleSuggest() {
        setLoading(true);
        const major = prompt('Enter your major:');
        const response = await getSuggestions(major);
        setResult(response);
        setLoading(false);
    }
    
    return (
        <div className="ai-assistant">
            <h2>🤖 AI Report Assistant</h2>
            
            <div className="mode-selector">
                <button onClick={() => setMode('improve')}>Improve</button>
                <button onClick={() => setMode('analyze')}>Analyze</button>
                <button onClick={() => setMode('generate')}>Generate</button>
                <button onClick={() => setMode('suggest')}>Suggest</button>
            </div>
            
            {mode === 'improve' && (
                <div>
                    <textarea 
                        value={content}
                        onChange={(e) => setContent(e.target.value)}
                        placeholder="Enter your report content..."
                    />
                    <button onClick={handleImprove} disabled={loading}>
                        {loading ? 'Processing...' : 'Improve Report'}
                    </button>
                </div>
            )}
            
            {mode === 'analyze' && (
                <div>
                    <textarea 
                        value={content}
                        onChange={(e) => setContent(e.target.value)}
                        placeholder="Enter your report content..."
                    />
                    <button onClick={handleAnalyze} disabled={loading}>
                        {loading ? 'Analyzing...' : 'Analyze Report'}
                    </button>
                </div>
            )}
            
            {mode === 'generate' && (
                <div>
                    {points.map((point, index) => (
                        <input
                            key={index}
                            value={point}
                            onChange={(e) => {
                                const newPoints = [...points];
                                newPoints[index] = e.target.value;
                                setPoints(newPoints);
                            }}
                            placeholder={`Point ${index + 1}`}
                        />
                    ))}
                    <button onClick={() => setPoints([...points, ''])}>
                        Add Point
                    </button>
                    <button onClick={handleGenerate} disabled={loading}>
                        {loading ? 'Generating...' : 'Generate Report'}
                    </button>
                </div>
            )}
            
            {mode === 'suggest' && (
                <button onClick={handleSuggest} disabled={loading}>
                    {loading ? 'Getting Suggestions...' : 'Get Suggestions'}
                </button>
            )}
            
            {result && result.success && (
                <div className="result">
                    <h3>Result:</h3>
                    <pre>{JSON.stringify(result.data, null, 2)}</pre>
                </div>
            )}
        </div>
    );
}

export default AIReportAssistant;
```

---

## 🎯 Vue.js Integration

### Axios Setup

```javascript
// api.js
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api',
    headers: {
        'Accept': 'application/json'
    }
});

// Add token and language to requests
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // ✅ Add language header
    if (user.preferred_language) {
        config.headers['X-Language'] = user.preferred_language;
    }
    
    return config;
});

// Handle errors
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        
        if (error.response?.status === 403) {
            const data = error.response.data;
            
            if (data.account_status === 'pending_review') {
                alert('حسابك قيد المراجعة من قبل الإدارة');
                window.location.href = '/pending-review';
            } else if (data.account_status === 'rejected') {
                alert(`تم رفض حسابك: ${data.rejection_reason}`);
                window.location.href = '/account-rejected';
            } else if (data.account_status === 'suspended') {
                alert('حسابك معلق. يرجى التواصل مع الإدارة.');
                window.location.href = '/account-suspended';
            }
        }
        
        return Promise.reject(error);
    }
);

export default api;
```

---

### Using in Component

```vue
<template>
    <div>
        <h1>Opportunities</h1>
        
        <!-- Language Selector -->
        <div class="language-selector">
            <button @click="changeLanguage('ar')" :class="{ active: currentLang === 'ar' }">
                العربية
            </button>
            <button @click="changeLanguage('en')" :class="{ active: currentLang === 'en' }">
                English
            </button>
        </div>

        <div v-if="loading">Loading...</div>
        <div v-else>
            <div v-for="opp in opportunities" :key="opp.id" class="opportunity">
                <h3>{{ opp.title }}</h3>
                <p>{{ opp.description }}</p>
                <p>Provider: {{ opp.provider.user.name }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from './api';

const opportunities = ref([]);
const loading = ref(true);
const currentLang = ref('ar');

onMounted(async () => {
    try {
        const response = await api.get('/opportunities');
        opportunities.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch opportunities:', error);
    } finally {
        loading.value = false;
    }
});

async function changeLanguage(lang) {
    try {
        const response = await api.post('/user/language', { language: lang });
        
        if (response.data.success) {
            currentLang.value = lang;
            
            // Update user in localStorage
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            user.preferred_language = lang;
            localStorage.setItem('user', JSON.stringify(user));
            
            // Apply direction
            document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
            document.documentElement.lang = lang;
            
            // Reload to apply translations
            setTimeout(() => window.location.reload(), 500);
        }
    } catch (error) {
        console.error('Failed to change language:', error);
    }
}
</script>
```

---

### Composable for Auth

```javascript
// composables/useAuth.js
import { ref, computed } from 'vue';
import api from '../api';

const user = ref(null);
const token = ref(localStorage.getItem('token'));
const accountStatus = ref(null);
const language = ref('ar');

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    const isPendingReview = computed(() => accountStatus.value === 'pending_review');
    const isRejected = computed(() => accountStatus.value === 'rejected');
    const isSuspended = computed(() => accountStatus.value === 'suspended');
    
    async function login(email, password) {
        try {
            const response = await api.post('/login', { email, password });
            
            if (response.data.token) {
                token.value = response.data.token;
                localStorage.setItem('token', response.data.token);
                user.value = response.data.user;
                accountStatus.value = response.data.user.account_status;
                language.value = response.data.user.preferred_language || 'ar';
                
                // Apply direction
                applyLanguageDirection(language.value);
                
                return { success: true };
            }
            
            return { success: false };
        } catch (error) {
            return { 
                success: false, 
                error: error.response?.data?.message,
                accountStatus: error.response?.data?.account_status
            };
        }
    }
    
    async function changeLanguage(newLanguage) {
        try {
            const response = await api.post('/user/language', { language: newLanguage });
            
            if (response.data.success) {
                language.value = newLanguage;
                
                // Update user
                if (user.value) {
                    user.value.preferred_language = newLanguage;
                    localStorage.setItem('user', JSON.stringify(user.value));
                }
                
                applyLanguageDirection(newLanguage);
                
                return { success: true };
            }
            
            return { success: false };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    function applyLanguageDirection(lang) {
        const direction = lang === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.dir = direction;
        document.documentElement.lang = lang;
    }
    
    function logout() {
        token.value = null;
        user.value = null;
        accountStatus.value = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }
    
    async function fetchProfile() {
        try {
            const response = await api.get('/profile');
            user.value = response.data.user;
            accountStatus.value = response.data.user.account_status;
            
            if (response.data.user.preferred_language) {
                language.value = response.data.user.preferred_language;
                applyLanguageDirection(language.value);
            }
        } catch (error) {
            console.error('Failed to fetch profile:', error);
            logout();
        }
    }
    
    return {
        user,
        token,
        accountStatus,
        language,
        isAuthenticated,
        isPendingReview,
        isRejected,
        isSuspended,
        login,
        logout,
        fetchProfile,
        changeLanguage
    };
}
```

---

### AI Features Composable

```javascript
// composables/useAI.js
import { ref } from 'vue';
import api from '../api';

export function useAI() {
    const loading = ref(false);
    const error = ref(null);
    
    async function improveReport(content) {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await api.post('/student/ai/reports/improve', { content });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to improve report';
            return null;
        } finally {
            loading.value = false;
        }
    }
    
    async function analyzeReport(content) {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await api.post('/student/ai/reports/analyze', { content });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to analyze report';
            return null;
        } finally {
            loading.value = false;
        }
    }
    
    async function generateReport(points, context = '') {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await api.post('/student/ai/reports/generate', { 
                points, 
                context 
            });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to generate report';
            return null;
        } finally {
            loading.value = false;
        }
    }
    
    async function getSuggestions(major, weekNumber = 1, currentTasks = '') {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await api.post('/student/ai/reports/suggest', {
                major,
                week_number: weekNumber,
                current_tasks: currentTasks
            });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to get suggestions';
            return null;
        } finally {
            loading.value = false;
        }
    }
    
    return {
        loading,
        error,
        improveReport,
        analyzeReport,
        generateReport,
        getSuggestions
    };
}
```
---

## 🤖 AI Features for Supervisors

Supervisors can use the same AI features to help review and analyze student reports.

### Improve Report (Supervisor)

```javascript
async function supervisorImproveReport(content) {
    const response = await apiRequest('/supervisor/ai/reports/improve', {
        method: 'POST',
        body: JSON.stringify({ content })
    });
    
    return await response.json();
}

// Usage
const result = await supervisorImproveReport('تعلمت Laravel اليوم وعملت على database');

if (result.success) {
    console.log('Original:', result.data.original_content);
    console.log('Improved:', result.data.improved_content);
    console.log('Language:', result.data.detected_language);
}
```

### Analyze Report (Supervisor)

```javascript
async function supervisorAnalyzeReport(content) {
    const response = await apiRequest('/supervisor/ai/reports/analyze', {
        method: 'POST',
        body: JSON.stringify({ content })
    });
    
    return await response.json();
}

// Usage
const result = await supervisorAnalyzeReport('تعلمت Laravel اليوم وعملت على database');

if (result.success) {
    console.log('Quality Score:', result.data.quality_score);
    console.log('Grade:', result.data.grade);
    console.log('Strengths:', result.data.strengths);
    console.log('Weaknesses:', result.data.weaknesses);
    console.log('Feedback:', result.data.detailed_feedback);
}
```

### Generate Report (Supervisor)

```javascript
async function supervisorGenerateReport(points, context = '') {
    const response = await apiRequest('/supervisor/ai/reports/generate', {
        method: 'POST',
        body: JSON.stringify({ points, context })
    });
    
    return await response.json();
}

// Usage - Create example report for students
const result = await supervisorGenerateReport(
    [
        'تعلمت Laravel',
        'عملت على database',
        'طورت API'
    ],
    'مثال على تقرير أسبوعي جيد'
);

if (result.success) {
    console.log('Example Report:', result.data.generated_report);
}
```

### Get Suggestions (Supervisor)

```javascript
async function supervisorGetSuggestions(major, weekNumber = 1) {
    const response = await apiRequest('/supervisor/ai/reports/suggest', {
        method: 'POST',
        body: JSON.stringify({
            major,
            week_number: weekNumber
        })
    });
    
    return await response.json();
}

// Usage - Get suggestions to share with students
const result = await supervisorGetSuggestions('تقنية المعلومات', 3);

if (result.success) {
    console.log('Suggested Topics:', result.data.suggested_topics);
    console.log('Suggested Tasks:', result.data.suggested_tasks);
}
```

---

## 🎨 Supervisor AI Dashboard Component (React)

```javascript
// SupervisorAIAssistant.jsx
import { useState } from 'react';

function SupervisorAIAssistant() {
    const [content, setContent] = useState('');
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [mode, setMode] = useState('analyze');

    async function handleAnalyze() {
        setLoading(true);
        const response = await supervisorAnalyzeReport(content);
        setResult(response);
        setLoading(false);
    }

    async function handleImprove() {
        setLoading(true);
        const response = await supervisorImproveReport(content);
        setResult(response);
        setLoading(false);
    }

    return (
        <div className="supervisor-ai-assistant">
            <h2>👨‍🏫 Supervisor AI Assistant</h2>
            
            <div className="mode-selector">
                <button onClick={() => setMode('analyze')}>Analyze Report</button>
                <button onClick={() => setMode('improve')}>Improve Report</button>
            </div>

            <textarea 
                value={content}
                onChange={(e) => setContent(e.target.value)}
                placeholder="Paste student report here..."
                rows={10}
            />

            <button onClick={mode === 'analyze' ? handleAnalyze : handleImprove} disabled={loading}>
                {loading ? 'Processing...' : `Run ${mode}`}
            </button>

            {result && result.success && (
                <div className="result">
                    {mode === 'analyze' && (
                        <div>
                            <h3>Analysis Results:</h3>
                            <p>Quality Score: {result.data.quality_score}/100</p>
                            <p>Grade: {result.data.grade}</p>
                            <h4>Strengths:</h4>
                            <ul>
                                {result.data.strengths.map((s, i) => <li key={i}>{s}</li>)}
                            </ul>
                            <h4>Weaknesses:</h4>
                            <ul>
                                {result.data.weaknesses.map((w, i) => <li key={i}>{w}</li>)}
                            </ul>
                            <h4>Feedback:</h4>
                            <p>{result.data.detailed_feedback}</p>
                        </div>
                    )}

                    {mode === 'improve' && (
                        <div>
                            <h3>Improved Version:</h3>
                            <pre>{result.data.improved_content}</pre>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

export default SupervisorAIAssistant;
```

---

## 🎯 Supervisor AI Use Cases

### 1. Report Review Workflow

```javascript
async function reviewStudentReport(reportContent) {
    // Step 1: Analyze the report
    const analysis = await supervisorAnalyzeReport(reportContent);
    
    if (!analysis.success) {
        return { error: 'Failed to analyze report' };
    }

    // Step 2: Get improved version as example
    const improved = await supervisorImproveReport(reportContent);

    // Step 3: Prepare feedback for student
    return {
        analysis: analysis.data,
        example: improved?.data?.improved_content,
        grade: calculateGradeFromScore(analysis.data.quality_score),
        feedback: analysis.data.detailed_feedback
    };
}

function calculateGradeFromScore(score) {
    if (score >= 90) return 'A';
    if (score >= 80) return 'B';
    if (score >= 70) return 'C';
    if (score >= 60) return 'D';
    return 'F';
}
```

### 2. Batch Analysis for Multiple Reports

```javascript
async function analyzeMultipleReports(reports) {
    const results = [];
    
    for (const report of reports) {
        try {
            const analysis = await supervisorAnalyzeReport(report.content);
            
            results.push({
                studentId: report.student_id,
                weekNumber: report.week_number,
                qualityScore: analysis.data?.quality_score || 0,
                grade: analysis.data?.grade || 'N/A',
                strengths: analysis.data?.strengths || [],
                weaknesses: analysis.data?.weaknesses || []
            });

            // Wait to avoid rate limiting
            await new Promise(resolve => setTimeout(resolve, 6000));
        } catch (error) {
            console.error(`Failed to analyze report ${report.id}:`, error);
        }
    }

    return results;
}
```


---

### Admin User Management Composable

```javascript
// composables/useAdminUsers.js
import { ref } from 'vue';
import api from '../api';

export function useAdminUsers() {
    const users = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({});

    async function fetchUsers(filters = {}) {
        loading.value = true;
        error.value = null;

        try {
            const queryParams = new URLSearchParams(filters).toString();
            const endpoint = queryParams ? `/admin/users?${queryParams}` : '/admin/users';
            
            const response = await api.get(endpoint);
            users.value = response.data.data.data || [];
            meta.value = {
                total: response.data.data.total,
                current_page: response.data.data.current_page,
                last_page: response.data.data.last_page
            };
            
            return { success: true };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch users';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function createUser(userData) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post('/admin/users', userData);
            return { success: true, data: response.data.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to create user';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function updateUser(userId, userData) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put(`/admin/users/${userId}`, userData);
            return { success: true, data: response.data.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to update user';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function deleteUser(userId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.delete(`/admin/users/${userId}`);
            return { success: true };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to delete user';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function suspendUser(userId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post(`/admin/users/${userId}/suspend`);
            return { success: true, data: response.data.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to suspend user';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function activateUser(userId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post(`/admin/users/${userId}/activate`);
            return { success: true, data: response.data.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to activate user';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function resetPassword(userId, password) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post(`/admin/users/${userId}/reset-password`, {
                password,
                password_confirmation: password
            });
            return { success: true };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to reset password';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    return {
        users,
        loading,
        error,
        meta,
        fetchUsers,
        createUser,
        updateUser,
        deleteUser,
        suspendUser,
        activateUser,
        resetPassword
    };
}
```

---

## 📱 CORS Configuration

If you get CORS errors, update `config/cors.php`:

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## 🚨 Error Handling

### Safe Request Wrapper

```javascript
async function safeRequest(endpoint, options) {
    try {
        const response = await apiRequest(endpoint, options);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }
        
        return { success: true, data };
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, error: error.message };
    }
}

// Usage
const result = await safeRequest('/opportunities');

if (result.success) {
    console.log(result.data);
} else {
    alert(`Error: ${result.error}`);
}
```

---

### Global Error Handler

```javascript
// errorHandler.js
export function handleApiError(error) {
    if (error.response) {
        // Server responded with error
        switch (error.response.status) {
            case 401:
                alert('Session expired. Please login again.');
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/login';
                break;
                
            case 403:
                const data = error.response.data;
                
                if (data.account_status === 'pending_review') {
                    alert('حسابك قيد المراجعة من قبل الإدارة');
                    window.location.href = '/pending-review';
                } else if (data.account_status === 'rejected') {
                    alert(`تم رفض حسابك: ${data.rejection_reason}`);
                    window.location.href = '/account-rejected';
                } else if (data.account_status === 'suspended') {
                    alert('حسابك معلق. يرجى التواصل مع الإدارة.');
                    window.location.href = '/account-suspended';
                } else {
                    alert('You do not have permission to perform this action.');
                }
                break;
                
            case 404:
                alert('Resource not found.');
                break;
                
            case 422:
                const errors = error.response.data.errors;
                const messages = Object.values(errors).flat().join('\n');
                alert(`Validation errors:\n${messages}`);
                break;
                
            case 429:
                alert('Too many requests. Please try again later.');
                break;
                
            case 500:
                alert('Server error. Please try again later.');
                break;
                
            default:
                alert('An error occurred. Please try again.');
        }
    } else if (error.request) {
        // Request made but no response
        alert('Network error. Please check your connection.');
    } else {
        // Something else
        alert('An unexpected error occurred.');
    }
}
```

---

## ✅ Best Practices

### 1. Store Tokens Securely

```javascript
// ✅ Good
localStorage.setItem('token', token);

// ❌ Bad - Don't store in plain text in URL
window.location.href = `/dashboard?token=${token}`;
```

---

### 2. Handle Loading States

```javascript
function Component() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    
    useEffect(() => {
        async function fetchData() {
            try {
                setLoading(true);
                const result = await safeRequest('/opportunities');
                setData(result.data);
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        }
        
        fetchData();
    }, []);
    
    if (loading) return <div>Loading...</div>;
    if (error) return <div>Error: {error}</div>;
    
    return <div>{/* Render data */}</div>;
}
```

---

### 3. Use Environment Variables

```javascript
// .env
VITE_API_URL=http://127.0.0.1:8000/api

// In code
const API_BASE_URL = import.meta.env.VITE_API_URL;
```

---

### 4. Implement Retry Logic

```javascript
async function requestWithRetry(endpoint, options, retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            const response = await apiRequest(endpoint, options);
            if (response.ok) return response;
        } catch (error) {
            if (i === retries - 1) throw error;
            await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
        }
    }
}
```

---

### 5. Cache Responses

```javascript
const cache = new Map();

async function getCachedData(endpoint, ttl = 60000) {
    const cached = cache.get(endpoint);
    
    if (cached && Date.now() - cached.timestamp < ttl) {
        return cached.data;
    }
    
    const response = await apiRequest(endpoint);
    const data = await response.json();
    
    cache.set(endpoint, {
        data,
        timestamp: Date.now()
    });
    
    return data;
}
```

---

### 6. Handle AI Rate Limiting

```javascript
// AI endpoints are rate-limited to 10 requests per minute
async function safeAIRequest(endpoint, options) {
    try {
        const response = await apiRequest(endpoint, options);
        
        if (response.status === 429) {
            alert('Too many AI requests. Please wait a minute and try again.');
            return null;
        }
        
        return await response.json();
    } catch (error) {
        console.error('AI Request Error:', error);
        return null;
    }
}
```

---

### 7. Detect Language in UI

```javascript
function detectLanguage(text) {
    const arabicRegex = /[\u0600-\u06FF]/;
    return arabicRegex.test(text) ? 'arabic' : 'english';
}

// Usage
const language = detectLanguage(userInput);
console.log('Detected language:', language);
```

---

### 8. Apply Language Direction

```javascript
function applyLanguageDirection(language) {
    const direction = language === 'ar' ? 'rtl' : 'ltr';
    
    // Update HTML attributes
    document.documentElement.dir = direction;
    document.documentElement.lang = language;
    
    // Update body class
    document.body.classList.remove('ltr', 'rtl');
    document.body.classList.add(direction);
    
    // Save preference
    localStorage.setItem('preferred_language', language);
}

// Call on app initialization
const savedLang = localStorage.getItem('preferred_language') || 'ar';
applyLanguageDirection(savedLang);
```

---

### 9. Handle User Language Preference

```javascript
// Always send user's preferred language with requests
function getAuthHeaders() {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    return {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'X-Language': user.preferred_language || 'ar'
    };
}

// Usage
const response = await fetch(`${API_BASE_URL}/profile`, {
    headers: getAuthHeaders()
});
```

---

### 10. Admin User Management Best Practices

```javascript
// Always confirm destructive actions
async function deleteUserWithConfirmation(userId, userName) {
    const confirmed = confirm(
        `Are you sure you want to delete user "${userName}"?\n\n` +
        `This action cannot be undone and will delete all related data.`
    );
    
    if (!confirmed) {
        return { success: false, error: 'Cancelled by user' };
    }
    
    return await deleteUser(userId);
}

// Validate password strength
function validatePassword(password) {
    if (password.length < 8) {
        return { valid: false, error: 'Password must be at least 8 characters' };
    }
    
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    
    if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
        return { 
            valid: false, 
            error: 'Password must contain uppercase, lowercase, and numbers' 
        };
    }
    
    return { valid: true };
}
```

---

## 📞 Support

For frontend integration issues:

- 📖 Check [API Documentation](API.md)
- 🧪 Test with Postman first
- 🔍 Check browser console for errors
- 📋 Verify CORS configuration
- 🔐 Ensure token is valid
- 🤖 Verify Groq API key is set for AI features
- 🌍 Check language headers are being sent

---

## 🔑 Environment Variables for Frontend

```env
# .env.example
VITE_API_URL=http://127.0.0.1:8000/api
VITE_APP_NAME=Trinova
VITE_DEFAULT_LOCALE=ar
VITE_SUPPORTED_LOCALES=en,ar
```

---

## 📚 Translation Keys Reference

The API returns messages in the user's preferred language. Common translation keys:

| Category | Example Keys |
|----------|--------------|
| `auth` | `register_success`, `login_success`, `account_pending_review` |
| `validation` | `content_required`, `points_required`, `major_required` |
| `opportunity` | `created`, `updated`, `closed`, `reopened` |
| `application` | `submitted`, `withdrawn`, `status_updated` |
| `report` | `submitted`, `reviewed`, `save_error_prefix` |
| `evaluation` | `saved`, `final_calculated` |
| `message` | `sent`, `marked_read` |
| `notification` | `not_found`, `marked_read`, `deleted` |
| `ai` | `improve_success`, `analyze_success`, `generate_success` |
| `admin` | `user_created`, `user_deleted`, `user_suspended` |
| `certificate` | `generated`, `not_found` |
| `general` | `unauthorized`, `forbidden`, `server_error` |

---

<div align="center">

**📖 Back to [README.md](../README.md) | [API Documentation](API.md)**

**Made with ❤️ by Trinova Team**

**Powered by Groq AI 🤖**

**Supports: العربية 🇸🇦 | English 🇬🇧**

</div>
