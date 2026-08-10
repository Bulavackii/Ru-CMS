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
            // Пароль принят, но вход ЕЩЁ НЕ СОСТОЯЛСЯ. Раньше здесь просто
            // шёл редирект, а сам вход уже был выполнен строкой выше
            // (authenticate() зовёт Auth::attempt). Страница ввода кода
            // закрыта middleware `guest` — вошедшего с неё сразу уводило на
            // сайт, и код не спрашивался НИ У КОГО: пароля хватало и
            // администратору, и обычному пользователю.
            Auth::logout();

            // Новый идентификатор сессии до того, как в ней окажется
            // отметка о пройденном пароле, — защита от фиксации сессии.
            $request->session()->regenerate();

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
        // Администратор попадает на главную панели, а не в «Модули»: там
        // сводка по сайту, быстрые действия и последние события — с неё
        // работу и начинают. Раздел модулей открывают редко и по делу.
        $redirectTo = $user->is_admin
            ? route('admin.dashboard')
            : '/dashboard';

        return redirect()->intended($redirectTo);
    }
}
