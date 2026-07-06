<?php

namespace App\Http\Controllers;

use App\Models\InternshipOpportunity;
use App\Http\Requests\SearchOpportunitiesRequest;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    /**
     * عرض جميع الفرص المتاحة (للطلاب)
     */
    public function index(SearchOpportunitiesRequest $request)
    {
        $query = InternshipOpportunity::query()
            ->with(['provider.user'])
            ->where('status', 'open')
            ->where('application_deadline', '>=', now());

        // 1️⃣ البحث النصي (في العنوان، الوصف، واسم الشركة)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('required_major', 'like', "%{$searchTerm}%")
                  ->orWhereHas('provider.user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('organization_name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // 2️⃣ الفلترة حسب التخصص
        if ($request->filled('major')) {
            $query->where('required_major', 'like', "%{$request->major}%");
        }

        // 3️⃣ الفلترة حسب الموقع
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // 4️⃣ الفلترة حسب نوع العمل (عن بعد)
        if ($request->has('is_remote')) {
            $query->where('is_remote', $request->boolean('is_remote'));
        }

        // 5️⃣ الفلترة حسب الراتب (مدفوع/غير مدفوع)
        if ($request->has('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        // 6️⃣ الفلترة حسب نطاق الراتب
        if ($request->filled('min_salary')) {
            $query->where('salary', '>=', $request->min_salary);
        }
        if ($request->filled('max_salary')) {
            $query->where('salary', '<=', $request->max_salary);
        }

        // 7️⃣ الترتيب (Sorting)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 8️⃣ Pagination (عدد النتائج في الصفحة)
        $perPage = $request->input('per_page', 15);
        $opportunities = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $opportunities,
            'filters_applied' => [
                'search' => $request->search,
                'major' => $request->major,
                'location' => $request->location,
                'is_remote' => $request->is_remote,
                'is_paid' => $request->is_paid,
                'salary_range' => [$request->min_salary, $request->max_salary],
            ],
            'meta' => [
                'total' => $opportunities->total(),
                'current_page' => $opportunities->currentPage(),
                'last_page' => $opportunities->lastPage(),
                'per_page' => $opportunities->perPage(),
            ]
        ]);
    }

    /**
     * عرض تفاصيل فرصة واحدة
     */
    public function show(InternshipOpportunity $opportunity)
    {
        return response()->json([
            'opportunity' => $opportunity->load('provider.user')
        ]);
    }

    /**
     * إنشاء فرصة جديدة (للمزودين فقط)
     */
    public function store(Request $request)
    {
        // ✅ تعريف $providerUser أولاً
        $providerUser = $request->user();
        $provider = $providerUser->provider;

        // ✅ التحقق من وجود provider record
        if (!$provider) {
            return response()->json([
                'message' => __('messages.auth.incomplete_provider_data')
            ], 400);
        }

        // ✅ التحقق من حالة حساب المزود (يجب أن يكون active)
        if ($providerUser->account_status !== 'active') {
            return response()->json([
                'message' => __('messages.opportunity.account_not_active'),
                'account_status' => $providerUser->account_status,
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'required_major' => 'required|string',
            'required_skills' => 'nullable|array',
            'available_positions' => 'required|integer|min:1',
            'location' => 'required|string',
            'is_remote' => 'boolean',
            'duration_months' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'application_deadline' => 'required|date|after:today',
            'salary' => 'nullable|numeric|min:0',
            'is_paid' => 'boolean',
        ]);

        $validated['provider_id'] = $provider->id;
        $validated['status'] = 'open';

        $opportunity = InternshipOpportunity::create($validated);

        return response()->json([
            'message' => __('messages.opportunity.created'),
            'opportunity' => $opportunity
        ], 201);
    }

    /**
     * تحديث فرصة (للمزود فقط)
     */
    public function update(Request $request, InternshipOpportunity $opportunity)
    {
        $providerUser = $request->user();
        $provider = $providerUser->provider;

        // ✅ التحقق من وجود provider record
        if (!$provider) {
            return response()->json(['message' => __('messages.auth.incomplete_provider_data')], 400);
        }

        // ✅ التحقق من حالة الحساب
        if ($providerUser->account_status !== 'active') {
            return response()->json([
                'message' => __('messages.opportunity.cannot_modify'),
                'account_status' => $providerUser->account_status,
            ], 403);
        }

        // التحقق من أن الفرصة تعود للمزود الحالي
        if ($opportunity->provider_id !== $provider->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'nullable|string',
            'required_major' => 'sometimes|string',
            'available_positions' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string',
            'application_deadline' => 'sometimes|date|after:today',
            'status' => 'sometimes|in:open,closed,draft,archived',
        ]);

        $opportunity->update($validated);

        return response()->json([
            'message' => __('messages.opportunity.updated'),
            'opportunity' => $opportunity
        ]);
    }

    /**
     * إغلاق فرصة (للمزود فقط)
     */
    public function close(Request $request, InternshipOpportunity $opportunity)
    {
        $providerUser = $request->user();
        $provider = $providerUser->provider;

        // ✅ التحقق من وجود provider record
        if (!$provider) {
            return response()->json(['message' => __('messages.auth.incomplete_provider_data')], 400);
        }

        // ✅ التحقق من حالة الحساب
        if ($providerUser->account_status !== 'active') {
            return response()->json([
                'message' => __('messages.opportunity.cannot_close'),
                'account_status' => $providerUser->account_status,
            ], 403);
        }

        if ($opportunity->provider_id !== $provider->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        $opportunity->update(['status' => 'closed']);

        return response()->json([
            'message' => __('messages.opportunity.closed'),
            'opportunity' => $opportunity
        ]);
    }

    /**
     * إعادة فتح فرصة (للمزود فقط)
     */
    public function reopen(Request $request, InternshipOpportunity $opportunity)
    {
        $providerUser = $request->user();
        $provider = $providerUser->provider;

        // ✅ التحقق من وجود provider record
        if (!$provider) {
            return response()->json(['message' => __('messages.auth.incomplete_provider_data')], 400);
        }

        // ✅ التحقق من حالة الحساب
        if ($providerUser->account_status !== 'active') {
            return response()->json([
                'message' => __('messages.opportunity.cannot_reopen'),
                'account_status' => $providerUser->account_status,
            ], 403);
        }

        if ($opportunity->provider_id !== $provider->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        // التحقق من أن الفرصة مغلقة
        if ($opportunity->status !== 'closed') {
            return response()->json([
                'message' => __('messages.opportunity.cannot_reopen_closed')
            ], 400);
        }

        // التحقق من أن الموعد النهائي لم ينتهي
        if ($opportunity->application_deadline < now()) {
            return response()->json([
                'message' => __('messages.opportunity.deadline_passed')
            ], 400);
        }

        $opportunity->update(['status' => 'open']);

        return response()->json([
            'message' => __('messages.opportunity.reopened'),
            'opportunity' => $opportunity
        ]);
    }

    /**
     * عرض فرص المزود الحالي
     */
    public function myOpportunities(Request $request)
    {
        $providerUser = $request->user();
        $provider = $providerUser->provider;

        // ✅ التحقق من وجود provider record
        if (!$provider) {
            return response()->json([
                'message' => __('messages.auth.incomplete_provider_data'),
                'opportunities' => []
            ], 400);
        }

        $opportunities = InternshipOpportunity::where('provider_id', $provider->id)
            ->withCount('applications')
            ->get();

        return response()->json(['opportunities' => $opportunities]);
    }
}
