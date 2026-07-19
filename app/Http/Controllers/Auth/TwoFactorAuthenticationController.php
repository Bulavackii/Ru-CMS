<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
use App\Services\LoginHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 🔐 TwoFactorAuthenticationController
 *
 * Контроллер для обработки двухфакторной аутентификации
 */
class TwoFactorAuthenticationController extends Controller
{
    protected SecurityService $securityService;
    protected LoginHistoryService $loginHistoryService;

    public function __construct(
        SecurityService $securityService,
        LoginHistoryService $loginHistoryService
    ) {
        $this->securityService = $securityService;
        $this->loginHistoryService = $loginHistoryService;
        $this->middleware('guest')->except(['disable']);
    }

    /**
     * Показать форму ввода 2FA кода
     */
    public function show(Request $request): View
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    /**
     * Обработать 2FA код
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('login.id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }

        // Проверяем код 2FA (Laravel автоматически расшифровывает через cast)
        $isValid = $this->securityService->verify2FACode(
            $user->two_factor_secret,
            $request->input('code')
        );

        if (!$isValid) {
            // Логируем неудачную попытку 2FA
            $this->loginHistoryService->logLoginAttempt(
                $user,
                $user->email,
                $request,
                'failed',
                '2fa_failed'
            );

            return back()->withErrors([
                'code' => 'Неверный код. Пожалуйста, попробуйте еще раз.',
            ]);
        }

        // Успешная 2FA - завершаем вход
        Auth::loginUsingId($userId, $request->session()->get('login.remember', false));

        $request->session()->regenerate();
        $request->session()->forget(['login.id', 'login.remember']);

        // Логируем успешный вход
        $this->loginHistoryService->logLoginAttempt(
            $user,
            $user->email,
            $request,
            'success'
        );

        $this->loginHistoryService->updateLastLogin($user, $request);

        Log::info('User logged in with 2FA', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        $redirectTo = $user->is_admin 
            ? '/admin/modules' 
            : '/dashboard';

        return redirect()->intended($redirectTo);
    }
}

