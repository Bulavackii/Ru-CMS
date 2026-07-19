<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 📱 SmsService - Сервис для отправки SMS
 * 
 * Поддерживает:
 * - SMS.ru
 * - Twilio
 */
class SmsService
{
    private string $provider;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'smsru');
    }

    /**
     * 📱 Отправить SMS
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);

        return match($this->provider) {
            'smsru' => $this->sendViaSmsru($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            default => false,
        };
    }

    /**
     * 📱 Отправить через SMS.ru
     */
    private function sendViaSmsru(string $phone, string $message): bool
    {
        try {
            $apiId = config('services.sms.smsru.api_id');
            
            if (!$apiId) {
                Log::warning('SMS.ru API ID not configured');
                return false;
            }

            $response = Http::get('https://sms.ru/sms/send', [
                'api_id' => $apiId,
                'to' => $phone,
                'msg' => $message,
                'json' => 1,
            ]);

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'OK') {
                return true;
            }

            Log::error('SMS.ru send failed', ['response' => $data]);
            return false;
        } catch (\Exception $e) {
            Log::error('SMS.ru exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 📱 Отправить через Twilio
     */
    private function sendViaTwilio(string $phone, string $message): bool
    {
        try {
            $accountSid = config('services.sms.twilio.account_sid');
            $authToken = config('services.sms.twilio.auth_token');
            $from = config('services.sms.twilio.from');

            if (!$accountSid || !$authToken || !$from) {
                Log::warning('Twilio credentials not configured');
                return false;
            }

            $response = Http::withBasicAuth($accountSid, $authToken)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Twilio send failed', ['response' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Twilio exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 📞 Нормализовать номер телефона
     */
    private function normalizePhone(string $phone): string
    {
        // Удалить все кроме цифр
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Если начинается с 8, заменить на 7
        if (str_starts_with($phone, '8')) {
            $phone = '7' . substr($phone, 1);
        }

        // Если не начинается с +, добавить
        if (!str_starts_with($phone, '7') && !str_starts_with($phone, '+')) {
            $phone = '7' . $phone;
        }

        return $phone;
    }
}

