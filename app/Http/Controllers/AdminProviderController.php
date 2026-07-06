<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminProviderController extends Controller
{
    /**
     * قائمة المزودين قيد المراجعة
     */
    public function pendingProviders(Request $request): JsonResponse
    {
        $providers = User::where('role', 'provider')
            ->where('account_status', 'pending_review')
            ->with('provider')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $providers->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'email' => $provider->email,
                    'phone' => $provider->phone,
                    'organization_name' => $provider->provider?->organization_name,
                    'organization_type' => $provider->provider?->organization_type,
                    'address' => $provider->provider?->address,
                    'city' => $provider->provider?->city,
                    'registered_at' => $provider->created_at,
                ];
            }),
            'meta' => [
                'total' => $providers->total(),
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
            ],
        ]);
    }

    /**
     * الموافقة على مزود
     */
    public function approveProvider(Request $request, int $providerId): JsonResponse
    {
        $provider = User::where('id', $providerId)
            ->where('role', 'provider')
            ->first();

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'المزود غير موجود',
            ], 404);
        }

        if ($provider->account_status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'الحساب موثق بالفعل',
            ], 400);
        }

        $provider->approve($request->user()->id);

        // ✅ إرسال إشعار للمزود بالموافقة
        $provider->notify(new \App\Notifications\ProviderAccountApproved($provider));

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على حساب المزود بنجاح',
        ]);
    }

    /**
     * رفض مزود
     */
    public function rejectProvider(Request $request, int $providerId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $provider = User::where('id', $providerId)
            ->where('role', 'provider')
            ->first();

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'المزود غير موجود',
            ], 404);
        }

        $provider->reject($request->reason, $request->user()->id);

        // ✅ إرسال إشعار للمزود بالرفض
        $provider->notify(new \App\Notifications\ProviderAccountRejected($provider, $request->reason));

        return response()->json([
            'success' => true,
            'message' => 'تم رفض حساب المزود',
        ]);
    }

    /**
     * قائمة جميع المزودين (للمدير)
     */
    public function allProviders(Request $request): JsonResponse
    {
        $providers = User::where('role', 'provider')
            ->with('provider')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $providers,
        ]);
    }
}
