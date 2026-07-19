<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 💳 SubscriptionController - Управление подписками и промокодами
 */
class SubscriptionController extends Controller
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * 📋 Список подписок
     */
    public function index()
    {
        $subscriptions = DB::table('subscriptions')
            ->join('users', 'subscriptions.user_id', '=', 'users.id')
            ->select('subscriptions.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('subscriptions.created_at', 'desc')
            ->paginate(20);

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    /**
     * 🎟️ Управление промокодами
     */
    public function promoCodes()
    {
        $promoCodes = DB::table('promo_codes')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.subscriptions.promo-codes', compact('promoCodes'));
    }

    /**
     * ➕ Создание промокода
     */
    public function createPromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:promo_codes,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        DB::table('promo_codes')->insert([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'usage_limit' => $request->usage_limit,
            'expires_at' => $request->expires_at ? now()->parse($request->expires_at) : null,
            'reusable' => $request->boolean('reusable'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.subscriptions.promo-codes')
            ->with('success', 'Промокод создан успешно');
    }

    /**
     * 🎟️ Применить промокод
     */
    public function applyPromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'plan' => 'required|string|in:basic,pro,enterprise',
        ]);

        $result = $this->subscriptionService->applyPromoCode(
            $request->code,
            $request->plan
        );

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }
}

