<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Services\SecurityService;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * 🔐 TwoFactorSetupController
 *
 * Контроллер для настройки двухфакторной аутентификации
 */
class TwoFactorSetupController extends Controller implements HasMiddleware
{
    /**
     * В Laravel 11+ $this->middleware() в контроллерах удалён —
     * вызов падал бы с «Call to undefined method».
     */
    public static function middleware(): array
    {
        return ['auth'];
    }

    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Показать форму настройки 2FA
     */
    public function show(): View
    {
        $user = Auth::user();
        
        // Генерируем секрет, если еще не настроен
        if (!$user->two_factor_secret) {
            $secret = $this->securityService->generate2FASecret();
            $qrCodeUrl = $this->securityService->getQRCodeUrl(
                $user->email,
                $secret,
                config('app.name', 'Nexum Core')
            );
            
            // Сохраняем временный секрет в сессии (до подтверждения)
            session(['2fa_temp_secret' => $secret]);

            return view('auth.two-factor-setup', [
                'secret' => $secret,
                'qrCodeUrl' => $qrCodeUrl,
                // Картинку рисуем здесь, а не во вьюхе: раньше в тег картинки
                // подставлялась сама строка otpauth://… — это содержимое кода,
                // а не изображение, и на странице была пустая рамка.
                // Сканировать было нечего, оставался только ручной ввод ключа.
                'qrCodeSvg' => $qrCodeUrl ? QrCode::svg($qrCodeUrl, 5) : null,
            ]);
        }

        return view('auth.two-factor-status', [
            'enabled' => $user->hasTwoFactorEnabled(),
        ]);
    }

    /**
     * Включить 2FA (после подтверждения кода)
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $tempSecret = session('2fa_temp_secret');

        if (!$tempSecret) {
            return back()->withErrors([
                'code' => 'Сессия истекла. Пожалуйста, начните настройку заново.',
            ]);
        }

        // Проверяем код
        $isValid = $this->securityService->verify2FACode($tempSecret, $request->input('code'));

        if (!$isValid) {
            return back()->withErrors([
                'code' => 'Неверный код. Пожалуйста, попробуйте еще раз.',
            ]);
        }

        // Генерируем recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Сохраняем секрет и включаем 2FA (используем встроенное шифрование Laravel)
        $user->update([
            'two_factor_secret' => $tempSecret, // Laravel автоматически зашифрует через cast
            'two_factor_recovery_codes' => $recoveryCodes, // Laravel автоматически зашифрует через cast
            'two_factor_enabled' => true,
        ]);

        session()->forget('2fa_temp_secret');

        Log::info('2FA enabled for user', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return redirect()->route('two-factor.setup')
            ->with('success', 'Двухфакторная аутентификация успешно включена!')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Отключить 2FA
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();
        
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
        ]);

        Log::info('2FA disabled for user', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return redirect()->route('two-factor.setup')
            ->with('success', 'Двухфакторная аутентификация отключена.');
    }

    /**
     * Генерация recovery codes
     */
    protected function generateRecoveryCodes(int $count = 8): array
    {
        // random_int, а не str_shuffle: коды восстановления заменяют собой
        // второй рубеж входа, а str_shuffle берёт числа из обычного
        // генератора Mt19937 — зная его состояние, весь набор
        // предсказывается. Плюс перемешивание алфавита не давало повторов
        // символов внутри кода, что само по себе сужало перебор.
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $last = strlen($alphabet) - 1;
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = '';

            for ($j = 0; $j < 10; $j++) {
                $code .= $alphabet[random_int(0, $last)];
            }

            $codes[] = $code;
        }

        return $codes;
    }
}

