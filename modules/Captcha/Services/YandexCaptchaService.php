<?php

namespace Modules\Captcha\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🔒 Сервис Яндекс.Капча
 * 
 * Интеграция с Яндекс.Капча (SmartCaptcha)
 * Документация: https://yandex.ru/dev/smartcaptcha/
 */
class YandexCaptchaService
{
    private string $clientKey;
    private string $serverKey;
    private string $apiUrl = 'https://smartcaptcha.yandexcloud.net';

    public function __construct(array $config)
    {
        $this->clientKey = $config['client_key'] ?? '';
        $this->serverKey = $config['server_key'] ?? '';
    }

    /**
     * Проверка токена капчи
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        if (empty($token) || empty($this->serverKey)) {
            return false;
        }

        try {
            $response = Http::post("{$this->apiUrl}/validate", [
                'secret' => $this->serverKey,
                'token' => $token,
                'ip' => $ip ?? request()->ip(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['status'] === 'ok';
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Yandex Captcha verification error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Генерация HTML для вставки капчи
     */
    public function render(array $options = []): string
    {
        if (empty($this->clientKey)) {
            return '<div class="alert alert-warning">Яндекс.Капча не настроена</div>';
        }

        $theme = $options['theme'] ?? 'light';
        $size = $options['size'] ?? 'normal';
        $lang = $options['lang'] ?? 'ru';

        $widgetId = 'yandex-captcha-' . uniqid();

        $html = '<div class="yandex-captcha" id="' . $widgetId . '"></div>';
        $html .= '<script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>';
        $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof window.smartCaptcha !== "undefined") {
                    window.smartCaptcha.render("' . $widgetId . '", {
                        sitekey: "' . $this->clientKey . '",
                        theme: "' . $theme . '",
                        size: "' . $size . '",
                        lang: "' . $lang . '"
                    });
                }
            });
        </script>';

        return $html;
    }

    /**
     * Получить клиентский ключ для фронтенда
     */
    public function getClientKey(): string
    {
        return $this->clientKey;
    }
}





