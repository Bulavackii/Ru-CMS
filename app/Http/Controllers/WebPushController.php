<?php

namespace App\Http\Controllers;

use App\Models\WebPushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * 📱 Контроллер для управления Web Push подписками
 */
class WebPushController extends Controller
{
    /**
     * 📝 Подписаться на Web Push уведомления
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|url|max:500',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $subscription = WebPushSubscription::updateOrCreate(
                [
                    'endpoint' => $request->input('endpoint'),
                ],
                [
                    'user_id' => Auth::id(),
                    'public_key' => $request->input('keys.p256dh'),
                    'auth_token' => $request->input('keys.auth'),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'active' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Подписка успешно создана',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            Log::error('Web push subscription error', [
                'error' => $e->getMessage(),
                'endpoint' => $request->input('endpoint'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании подписки',
            ], 500);
        }
    }

    /**
     * ❌ Отписаться от Web Push уведомлений
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $subscription = WebPushSubscription::where('endpoint', $request->input('endpoint'))
                ->where('user_id', Auth::id())
                ->first();

            if ($subscription) {
                $subscription->update(['active' => false]);
                // Или удалить: $subscription->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Подписка отменена',
            ]);
        } catch (\Exception $e) {
            Log::error('Web push unsubscribe error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене подписки',
            ], 500);
        }
    }

    /**
     * 📊 Получить публичный VAPID ключ
     */
    public function getPublicKey()
    {
        $publicKey = config('webpush.vapid.public_key');

        if (empty($publicKey)) {
            return response()->json([
                'success' => false,
                'message' => 'VAPID ключи не настроены',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'publicKey' => $publicKey,
        ]);
    }
}

