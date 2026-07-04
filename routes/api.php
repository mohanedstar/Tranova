<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\WeeklyReportController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationCalculationController;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Controllers\CertificateController;

// ==================== Public Routes ====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/opportunities', [OpportunityController::class, 'index']);
Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show']);

// ==================== Password Reset Routes (Public) ====================
Route::prefix('password')->middleware('throttle:5,1')->group(function () {
    Route::post('/forgot', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/verify-token', [PasswordResetController::class, 'verifyToken']);
    Route::post('/reset', [PasswordResetController::class, 'resetPassword']);
});
   // ==================== Email Verification Routes ====================
Route::prefix('email')->group(function () {
    Route::get('/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/resend', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1');

    Route::get('/verify-notice', [VerifyEmailController::class, 'notice']);
});


// ==================== Authenticated Routes ====================
Route::middleware('auth:sanctum')->group(function () {

    // Profile & Logout
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ==================== Dashboards ====================
    Route::get('/student/dashboard', [DashboardController::class, 'studentDashboard'])->middleware('role:student');
    Route::get('/provider/dashboard', [DashboardController::class, 'providerDashboard'])->middleware('role:provider');
    Route::get('/supervisor/dashboard', [DashboardController::class, 'supervisorDashboard'])->middleware('role:supervisor');
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->middleware('role:admin');

    // ==================== Student Routes ====================
    Route::middleware('role:student')->prefix('student')->group(function () {
        // Applications
        Route::post('/opportunities/{opportunity}/apply', [ApplicationController::class, 'store']);
        Route::get('/applications', [ApplicationController::class, 'myApplications']);
        Route::post('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw']);

        // Reports
        Route::post('/reports', [WeeklyReportController::class, 'store']);
        Route::get('/reports', [WeeklyReportController::class, 'myReports']);

        // Evaluations
        Route::get('/evaluations', [EvaluationController::class, 'myEvaluations']);
        Route::get('/evaluations/opportunity/{opportunityId}/final', [EvaluationCalculationController::class, 'showStudentEvaluation']);

        // Certificates
        Route::get('/certificates', [CertificateController::class, 'myCertificates']);
        Route::get('/certificates/download', [CertificateController::class, 'downloadMyCertificate']);
        Route::get('/certificates/preview', [CertificateController::class, 'previewMyCertificate']);
    });

    // ==================== Provider Routes ====================
    Route::middleware('role:provider')->prefix('provider')->group(function () {
        Route::get('/opportunities', [OpportunityController::class, 'myOpportunities']);
        Route::post('/opportunities', [OpportunityController::class, 'store']);
        Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update']);
        Route::post('/opportunities/{opportunity}/close', [OpportunityController::class, 'close']);

        Route::get('/opportunities/{opportunity}/applications', [ApplicationController::class, 'indexForOpportunity']);
        Route::post('/applications/{application}/review', [ApplicationController::class, 'review']);

        Route::post('/evaluations', [EvaluationController::class, 'storeProviderEvaluation']);
    });

    // ==================== Supervisor Routes ====================
    Route::middleware('role:supervisor')->prefix('supervisor')->group(function () {
        Route::get('/reports', [WeeklyReportController::class, 'studentReports']);
        Route::post('/reports/{report}/review', [WeeklyReportController::class, 'review']);

        Route::post('/evaluations', [EvaluationController::class, 'storeSupervisorEvaluation']);
    });

    // ==================== Admin Routes ====================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/students', [AdminController::class, 'students']);
        Route::get('/supervisors', [AdminController::class, 'supervisors']);
        Route::post('/assign-supervisor', [AdminController::class, 'assignSupervisor']);
        Route::post('/records/{record}/approve', [AdminController::class, 'approveRecord']);
        Route::get('/statistics', [AdminController::class, 'statistics']);

        Route::get('/certificates', [CertificateController::class, 'allCertificates']);
        Route::post('/records/{recordId}/generate-certificate', [CertificateController::class, 'generateCertificate']);
        Route::get('/students/{studentId}/certificate', [CertificateController::class, 'downloadStudentCertificate']);

        Route::get('/evaluations/final', [EvaluationCalculationController::class, 'indexAll']);
        Route::get('/evaluations/statistics', [EvaluationCalculationController::class, 'statistics']);
        Route::post('/students/{studentId}/opportunities/{opportunityId}/calculate', [EvaluationCalculationController::class, 'calculateAndCreateRecord']);
    });

    // ==================== Messages (All Authenticated Users) ====================
    Route::prefix('messages')->group(function () {
        Route::post('/', [MessageController::class, 'store']);
        Route::get('/inbox', [MessageController::class, 'inbox']);
        Route::get('/sent', [MessageController::class, 'sent']);
        Route::post('/{message}/read', [MessageController::class, 'markAsRead']);
    });

    // ==================== Notifications (All Authenticated Users) ====================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'clearAll']);
    });


}); // <--- نهاية مجموعة auth:sanctum
