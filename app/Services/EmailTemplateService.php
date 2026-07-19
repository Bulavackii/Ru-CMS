<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 📧 EmailTemplateService - Сервис для управления шаблонами писем
 */
class EmailTemplateService
{
    /**
     * 📝 Получить шаблон
     */
    public function getTemplate(string $key, array $variables = []): ?array
    {
        $template = DB::table('email_templates')->where('key', $key)->first();

        if (!$template) {
            return null;
        }

        return [
            'subject' => $this->replaceVariables($template->subject, $variables),
            'body' => $this->replaceVariables($template->body, $variables),
        ];
    }

    /**
     * 💾 Сохранить шаблон
     */
    public function saveTemplate(string $key, string $subject, string $body): bool
    {
        try {
            DB::table('email_templates')->updateOrInsert(
                ['key' => $key],
                [
                    'subject' => $subject,
                    'body' => $body,
                    'updated_at' => now(),
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save email template', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 🔄 Заменить переменные в тексте
     */
    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{{$key}}}", $value, $text);
        }
        return $text;
    }
}

