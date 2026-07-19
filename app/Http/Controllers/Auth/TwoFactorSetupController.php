<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
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
class TwoFactorSetupController extends Controller
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->middleware('auth');
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
                config('app.name', 'RU CMS')
            );
            
            // Сохраняем временный секрет в сессии (до подтверждения)
            session(['2fa_temp_secret' => $secret]);
            
            return view('auth.two-factor-setup', [
                'secret' => $secret,
                'qrCodeUrl' => $qrCodeUrl,
            ]);
        }

        return view('auth.two-factor-status', [
            'enabled' => $user->two_factor_enabled,
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
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10));
        }
        return $codes;
    }
}

