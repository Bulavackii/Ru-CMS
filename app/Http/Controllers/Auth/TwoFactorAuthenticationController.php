<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
class TwoFactorAuthenticationController extends Controller implements HasMiddleware
{
    /**
     * В Laravel 11+ $this->middleware() в контроллерах удалён —
     * вызов падал бы с «Call to undefined method».
     */
    public static function middleware(): array
    {
        return [new Middleware('guest', except: ['disable'])];
    }

    protected SecurityService $securityService;
    protected LoginHistoryService $loginHistoryService;

    public function __construct(
        SecurityService $securityService,
        LoginHistoryService $loginHistoryService
    ) {
        $this->securityService = $securityService;
        $this->loginHistoryService = $loginHistoryService;
    }

    /**
     * Показать форму ввода 2FA кода
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('login.id')) {
            // Тип возврата был объявлен как View, а здесь возвращается
            // редирект — заход на страницу без начатого входа падал с
            // TypeError и отдавал 500 вместо обычного возврата на форму.
            return redirect()->route('login')
                ->with('status', __('frontend.auth.tfa_password_first'));
        }

        return view('auth.two-factor');
    }

    /**
     * Обработать 2FA код
     */
    public function verify(Request $request): RedirectResponse
    {
        // Длина не фиксирована: кроме шестизначного кода из приложения
        // принимается код восстановления в десять знаков — иначе потерянный
        // телефон означал бы потерю доступа навсегда.
        $request->validate([
            'code' => 'required|string|max:16',
        ]);

        $userId = $request->session()->get('login.id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }

        $input = trim((string) $request->input('code'));

        // Проверяем код 2FA (Laravel автоматически расшифровывает через cast)
        $isValid = $this->securityService->verify2FACode(
            $user->two_factor_secret,
            $input
        );

        // Не подошёл — пробуем как код восстановления. Подошедший код
        // ВЫЧЁРКИВАЕТСЯ: он одноразовый, иначе подсмотренный листок с
        // кодами работал бы вечно.
        $byRecoveryCode = false;

        if (! $isValid) {
            $codes = $user->two_factor_recovery_codes ?? [];
            $used = array_search(strtoupper($input), array_map('strtoupper', $codes), true);

            if ($used !== false) {
                unset($codes[$used]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                $isValid = true;
                $byRecoveryCode = true;

                Log::warning('Вход по коду восстановления', [
                    'user_id' => $user->id,
                    'осталось_кодов' => count($codes),
                    'ip' => $request->ip(),
                ]);
            }
        }

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
            'по_коду_восстановления' => $byRecoveryCode,
        ]);

        // Тот же адрес, что и при обычном входе (LoginController): главная
        // панели. Здесь стоял «/admin/modules» — два контроллера одного и
        // того же входа уводили администратора в разные места.
        $redirectTo = $user->is_admin
            ? route('admin.dashboard')
            : '/dashboard';

        return redirect()->intended($redirectTo);
    }
}

