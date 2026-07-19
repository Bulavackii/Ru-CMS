<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\LoginHistoryService;
use App\Services\SecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 🔐 LoginController
 *
 * Контроллер для отображения формы входа и обработки логина пользователя.
 */
class LoginController extends Controller
{
    protected LoginHistoryService $loginHistoryService;
    protected SecurityService $securityService;

    public function __construct(LoginHistoryService $loginHistoryService, SecurityService $securityService)
    {
        $this->loginHistoryService = $loginHistoryService;
        $this->securityService = $securityService;
    }

    /**
     * 📄 showLoginForm()
     *
     * 🔓 Показывает форму авторизации
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * ✅ login()
     *
     * 🔐 Обрабатывает вход пользователя:
     *   - 🔍 Валидирует email и пароль через LoginRequest (с rate limiting)
     *   - 🧪 Проверяет учётные данные через Auth::attempt
     *   - 🔐 Проверяет 2FA, если включена
     *   - 🔄 Регенерирует сессию после входа (защита от фиксации)
     *   - 📊 Сохраняет историю входа
     *   - 📝 Логирует успешные и неудачные попытки входа
     *   - 🚫 В случае ошибки — возвращает с сообщением
     *
     * @param  LoginRequest  $request
     * @return RedirectResponse
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        // 🔐 Попытка аутентификации (с rate limiting внутри LoginRequest)
        $request->authenticate();

        $user = Auth::user();
        $email = $request->input('email');

        // 🔐 Проверка 2FA, если включена
        if ($user->hasTwoFactorEnabled()) {
            // Сохраняем email в сессии для повторной аутентификации после 2FA
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            // Редирект на страницу ввода 2FA кода
            return redirect()->route('two-factor.login');
        }

        // 🔄 Генерация новой сессии для безопасности
        $request->session()->regenerate();

        // 📊 Сохраняем историю входа
        $this->loginHistoryService->logLoginAttempt(
            $user,
            $email,
            $request,
            'success'
        );

        // Обновляем последний вход
        $this->loginHistoryService->updateLastLogin($user, $request);

        // 📝 Логирование успешного входа
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // ✅ Успешный вход — перенаправление
        $redirectTo = $user->is_admin
            ? '/admin/modules'
            : '/dashboard';

        return redirect()->intended($redirectTo);
    }
}
