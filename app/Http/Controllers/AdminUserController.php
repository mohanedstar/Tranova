<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * List all users with filters
     */
public function index(Request $request)
{
    // ✅ Dynamic operator for PostgreSQL/MySQL compatibility
    // PostgreSQL: ILIKE (case-insensitive)
    // MySQL: LIKE (case-insensitive by default)
    $searchOperator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

    $query = User::query();

    // Filter by role
    if ($request->filled('role')) {
        $query->where('role', $request->role);
    }

    // Filter by status
    if ($request->filled('status')) {
        $query->where('account_status', $request->status);
    }

    // Search by name or email
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search, $searchOperator) {
            $q->where('name', $searchOperator, "%{$search}%")
              ->orWhere('email', $searchOperator, "%{$search}%");
        });
    }

    $users = $query->with(['student', 'provider', 'supervisor'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return response()->json([
        'success' => true,
        'data' => $users,
    ]);
}

    /**
     * Get user details
     */
    public function show(User $user)
    {
        $user->load(['student', 'provider', 'supervisor']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Create new user (Admin only)
     */
/**
 * Create new user (Admin only)
 */
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'phone' => 'nullable|string|max:20',
        'role' => 'required|in:student,provider,supervisor,admin',
        'account_status' => 'nullable|in:active,pending_review,suspended',
    ]);

    $user = DB::transaction(function () use ($validated, $request) {
        // ✅ Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'account_status' => $validated['account_status'] ?? 'active',
            'email_verified_at' => now(), // Auto-verify admin-created users
        ]);

        // ✅ Create role-specific record
        switch ($validated['role']) {
            case 'student':
                $request->validate([
                    'student_id' => 'required|string|unique:students,student_id',
                    'major' => 'required|string',
                    'university' => 'required|string',
                    'year_of_study' => 'required|in:1,2,3,4,5',
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'student_id' => $request->student_id,
                    'major' => $request->major,
                    'university' => $request->university,
                    'year_of_study' => $request->year_of_study,
                ]);
                break;

            case 'provider':
                $request->validate([
                    'organization_name' => 'required|string',
                    'organization_type' => 'required|in:company,hospital,government,nonprofit,other',
                    'address' => 'required|string',
                    'city' => 'required|string',
                ]);

                Provider::create([
                    'user_id' => $user->id,
                    'organization_name' => $request->organization_name,
                    'organization_type' => $request->organization_type,
                    'address' => $request->address,
                    'city' => $request->city,
                    'country' => $request->country ?? 'Palestine',
                ]);
                break;

            case 'supervisor':
                $request->validate([
                    'employee_id' => 'required|string|unique:supervisors,employee_id',
                    'department' => 'required|string',
                    'academic_title' => 'required|in:professor,assistant_professor,lecturer,instructor',
                ]);

                Supervisor::create([
                    'user_id' => $user->id,
                    'employee_id' => $request->employee_id,
                    'department' => $request->department,
                    'academic_title' => $request->academic_title,
                ]);
                break;
        }

        return $user;
    });

    return response()->json([
        'success' => true,
        'message' => __('messages.admin.user_created'),
        'data' => $user->load(['student', 'provider', 'supervisor']),
    ], 201);
}

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => ['sometimes', Rule::in(['student', 'provider', 'supervisor', 'admin'])],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_updated'),
            'data' => $user,
        ]);
    }

    /**
     * Delete user (Admin only)
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.admin.cannot_delete_self'),
            ], 400);
        }

        DB::transaction(function () use ($user) {
            // Delete related records
            if ($user->student) {
                $user->student->delete();
            }
            if ($user->provider) {
                $user->provider->delete();
            }
            if ($user->supervisor) {
                $user->supervisor->delete();
            }

            // Delete tokens
            $user->tokens()->delete();

            // Delete user
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_deleted'),
        ]);
    }

    /**
     * Suspend user
     */
    public function suspend(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.admin.cannot_suspend_self'),
            ], 400);
        }

        $user->update(['account_status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_suspended'),
            'data' => $user,
        ]);
    }

    /**
     * Activate user
     */
    public function activate(User $user)
    {
        $user->update(['account_status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_activated'),
            'data' => $user,
        ]);
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        // Invalidate all tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.password_reset'),
        ]);
    }
}
