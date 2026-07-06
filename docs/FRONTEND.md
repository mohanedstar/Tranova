# 🎨 Frontend Integration Guide

<div align="center">

**Complete guide for integrating Trinova API with your frontend application - including AI-powered features**

![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript)
![React](https://img.shields.io/badge/React-Supported-61DAFB?style=flat-square&logo=react)
![Vue](https://img.shields.io/badge/Vue-Supported-4FC08D?style=flat-square&logo=vue.js)
![Axios](https://img.shields.io/badge/Axios-Supported-5A29E4?style=flat-square)
![AI](https://img.shields.io/badge/AI-Groq%20LLM-FF6B35?style=flat-square)

</div>

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Base URL](#-base-url)
- [Authentication Flow](#-authentication-flow)
- [Making Authenticated Requests](#-making-authenticated-requests)
- [Example: Get Opportunities](#-example-get-opportunities)
- [Example: Apply for Opportunity](#-example-apply-for-opportunity)
- [Example: Submit Weekly Report](#-example-submit-weekly-report)
- [Example: Download Certificate](#-example-download-certificate)
- [Example: Get Notifications](#-example-get-notifications)
- [Example: Send Message](#-example-send-message)
- [AI-Powered Features](#-ai-powered-features)
- [Admin Provider Management](#-admin-provider-management)
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
- 💼 How to handle opportunities and applications
- 📊 How to submit reports and evaluations
- 🏆 How to download certificates
- 🔔 How to handle notifications
- 💬 How to send messages
- 🤖 How to use AI-powered report features
- 🛡️ How to manage provider approvals (admin)

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
    }
    
    return { success: false, error: data.message };
}

// Usage
const result = await login('john@example.com', 'password123');

if (result.pendingReview) {
    // Show pending review page
    showPendingReviewPage();
} else if (result.needsVerification) {
    // Show email verification page
    showVerificationPage();
} else if (result.success) {
    // Redirect to dashboard
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
    
    const defaultHeaders = {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    };
    
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
    }
    
    return response;
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
    
    const response = await fetch(
        `${API_BASE_URL}/student/certificates/download`,
        {
            headers: {
                'Authorization': `Bearer ${token}`
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
    // Refresh pending providers list
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
                loadPendingProviders(); // Refresh list
            }
        }
    }
    
    async function handleReject(providerId) {
        const reason = prompt('Enter rejection reason:');
        if (reason) {
            const result = await rejectProvider(providerId, reason);
            if (result.success) {
                loadPendingProviders(); // Refresh list
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
    
    useEffect(() => {
        if (token) {
            fetchProfile();
        } else {
            setLoading(false);
        }
    }, [token]);
    
    async function fetchProfile() {
        try {
            const response = await fetch(`${API_BASE_URL}/profile`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                setUser(data.user);
                setAccountStatus(data.user.account_status);
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
            setToken(data.token);
            setUser(data.user);
            setAccountStatus(data.user.account_status);
            return { success: true };
        }
        
        return { 
            success: false, 
            error: data.message,
            accountStatus: data.account_status 
        };
    }
    
    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setToken(null);
        setUser(null);
        setAccountStatus(null);
    }
    
    return (
        <AuthContext.Provider value={{ 
            user, 
            token, 
            loading, 
            accountStatus,
            login, 
            logout,
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

function Dashboard() {
    const { user, logout, loading, accountStatus } = useAuth();
    
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
    
    return (
        <div>
            <h1>Welcome, {user.name}!</h1>
            <p>Role: {user.role}</p>
            <p>Email: {user.email}</p>
            <p>Account Status: {accountStatus}</p>
            <button onClick={logout}>Logout</button>
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
    const [mode, setMode] = useState('improve'); // improve, analyze, generate, suggest
    
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

// Add token to requests
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
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

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    const isPendingReview = computed(() => accountStatus.value === 'pending_review');
    const isRejected = computed(() => accountStatus.value === 'rejected');
    
    async function login(email, password) {
        try {
            const response = await api.post('/login', { email, password });
            
            if (response.data.token) {
                token.value = response.data.token;
                localStorage.setItem('token', response.data.token);
                user.value = response.data.user;
                accountStatus.value = response.data.user.account_status;
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
        } catch (error) {
            console.error('Failed to fetch profile:', error);
            logout();
        }
    }
    
    return {
        user,
        token,
        accountStatus,
        isAuthenticated,
        isPendingReview,
        isRejected,
        login,
        logout,
        fetchProfile
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

## 📞 Support

For frontend integration issues:

- 📖 Check [API Documentation](API.md)
- 🧪 Test with Postman first
- 🔍 Check browser console for errors
- 📋 Verify CORS configuration
- 🔐 Ensure token is valid
- 🤖 Verify Groq API key is set for AI features

---

## 🔑 Environment Variables for Frontend

```env
# .env.example
VITE_API_URL=http://127.0.0.1:8000/api
VITE_APP_NAME=Trinova
```

---

<div align="center">

**📖 Back to [README.md](../README.md) | [API Documentation](API.md)**

**Made with ❤️ by Trinova Team**

**Powered by Groq AI 🤖**

</div>
