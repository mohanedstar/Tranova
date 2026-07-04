# 🎨 Frontend Integration Guide

<div align="center">

**Complete guide for integrating Trinova API with your frontend application**

![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript)
![React](https://img.shields.io/badge/React-Supported-61DAFB?style=flat-square&logo=react)
![Vue](https://img.shields.io/badge/Vue-Supported-4FC08D?style=flat-square&logo=vue.js)
![Axios](https://img.shields.io/badge/Axios-Supported-5A29E4?style=flat-square)

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
- [React Integration](#-react-integration)
- [Vue.js Integration](#-vuejs-integration)
- [CORS Configuration](#-cors-configuration)
- [Error Handling](#-error-handling)
- [Best Practices](#-best-practices)

---

## 📋 Overview

This guide helps frontend developers integrate with the Trinova API using JavaScript, React, or Vue.js.

### What you'll learn:

- 🔐 How to authenticate users
- 📡 How to make API requests
- 💼 How to handle opportunities and applications
- 📊 How to submit reports and evaluations
- 🏆 How to download certificates
- 🔔 How to handle notifications
- 💬 How to send messages

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
        return { success: true, data };
    }
    
    return { success: false, error: data.message };
}

// Usage
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
        return { success: true, data };
    }
    
    return { success: false, error: data.message };
}

// Usage
login('john@example.com', 'password123');
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
        window.location.href = '/login';
        return;
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
    
    return data.opportunities.data;
}

// Usage
const opportunities = await getOpportunities({
    major: 'IT',
    is_remote: true,
    page: 1
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

if (result.success) {
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
            setUser(data.user);
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
            return { success: true };
        }
        
        return { success: false, error: data.message };
    }
    
    function logout() {
        localStorage.removeItem('token');
        setToken(null);
        setUser(null);
    }
    
    return (
        <AuthContext.Provider value={{ user, token, loading, login, logout }}>
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
    const { user, logout, loading } = useAuth();
    
    if (loading) {
        return <div>Loading...</div>;
    }
    
    if (!user) {
        return <div>Please login</div>;
    }
    
    return (
        <div>
            <h1>Welcome, {user.name}!</h1>
            <p>Role: {user.role}</p>
            <p>Email: {user.email}</p>
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

function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();
    
    if (loading) {
        return <div>Loading...</div>;
    }
    
    if (!user) {
        return <Navigate to="/login" replace />;
    }
    
    return children;
}

export default ProtectedRoute;
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

// Handle 401 errors
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            window.location.href = '/login';
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
                <p>Provider: {{ opp.provider.organization_name }}</p>
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
        opportunities.value = response.data.opportunities.data;
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

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    
    async function login(email, password) {
        const response = await api.post('/login', { email, password });
        
        if (response.data.token) {
            token.value = response.data.token;
            localStorage.setItem('token', response.data.token);
            user.value = response.data.user;
            return true;
        }
        
        return false;
    }
    
    function logout() {
        token.value = null;
        user.value = null;
        localStorage.removeItem('token');
    }
    
    async function fetchProfile() {
        const response = await api.get('/profile');
        user.value = response.data.user;
    }
    
    return {
        user,
        token,
        isAuthenticated,
        login,
        logout,
        fetchProfile
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
                window.location.href = '/login';
                break;
            case 403:
                alert('You do not have permission to perform this action.');
                break;
            case 404:
                alert('Resource not found.');
                break;
            case 422:
                const errors = error.response.data.errors;
                const messages = Object.values(errors).flat().join('\n');
                alert(`Validation errors:\n${messages}`);
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

## 📞 Support

For frontend integration issues:

- 📖 Check [API Documentation](API.md)
- 🧪 Test with Postman first
- 🔍 Check browser console for errors
- 📋 Verify CORS configuration
- 🔐 Ensure token is valid

---

<div align="center">

**📖 Back to [README.md](../README.md) | [API Documentation](API.md)**

**Made with ❤️ by Trinova Team**

</div>
