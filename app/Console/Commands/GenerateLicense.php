<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 🔑 Команда для генерации лицензионных ключей и промокодов
 * 
 * Доступна только для разработчика (проверка через .env или специальный ключ)
 */
class GenerateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:generate 
                            {type : Тип (license|promo)}
                            {--plan=basic : Тариф для лицензии (basic|pro|enterprise)}
                            {--months=12 : Срок действия в месяцах}
                            {--code= : Код промокода (если не указан, будет сгенерирован)}
                            {--discount-type=percentage : Тип скидки (percentage|fixed)}
                            {--discount-value=10 : Значение скидки}
                            {--usage-limit= : Лимит использования промокода}
                            {--expires-at= : Дата истечения промокода (Y-m-d)}
                            {--name= : Название промокода}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерация лицензионных ключей и промокодов (только для разработчика)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Проверка доступа (только для разработчика)
        if (!$this->isDeveloper()) {
            $this->error('❌ Доступ запрещен. Эта команда доступна только для разработчика.');
            $this->info('💡 Установите DEVELOPER_MODE=true в .env для доступа к этой команде.');
            return 1;
        }

        $type = $this->argument('type');

        if ($type === 'license') {
            return $this->generateLicense();
        } elseif ($type === 'promo') {
            return $this->generatePromoCode();
        } else {
            $this->error("❌ Неизвестный тип: {$type}. Используйте 'license' или 'promo'.");
            return 1;
        }
    }

    /**
     * Генерация лицензионного ключа
     */
    private function generateLicense(): int
    {
        $plan = $this->option('plan');
        $months = (int) $this->option('months');

        if (!in_array($plan, ['basic', 'pro', 'enterprise'])) {
            $this->error('❌ Неверный тариф. Используйте: basic, pro, enterprise');
            return 1;
        }

        $licenseKey = $this->generateLicenseKey();
        $expiresAt = now()->addMonths($months);

        $this->info('🔑 Генерация лицензионного ключа...');
        $this->newLine();

        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Лицензионный ключ', $licenseKey],
                ['Тариф', $plan],
                ['Срок действия', $expiresAt->format('Y-m-d H:i:s')],
                ['Месяцев', $months],
            ]
        );

        $this->newLine();
        $this->info('💾 Сохраните этот ключ для выдачи клиентам.');
        $this->info('📋 Ключ будет активирован при установке CMS.');

        // Сохраняем в файл для удобства
        $filePath = storage_path('app/licenses.txt');
        $content = sprintf(
            "%s | Plan: %s | Expires: %s | Generated: %s\n",
            $licenseKey,
            $plan,
            $expiresAt->format('Y-m-d'),
            now()->format('Y-m-d H:i:s')
        );

        file_put_contents($filePath, $content, FILE_APPEND);
        $this->info("✅ Ключ сохранен в: {$filePath}");

        return 0;
    }

    /**
     * Генерация промокода
     */
    private function generatePromoCode(): int
    {
        $code = $this->option('code') ?: strtoupper(Str::random(8));
        $discountType = $this->option('discount-type');
        $discountValue = (float) $this->option('discount-value');
        $usageLimit = $this->option('usage-limit') ? (int) $this->option('usage-limit') : null;
        $expiresAt = $this->option('expires-at') ? now()->parse($this->option('expires-at')) : null;
        $name = $this->option('name');

        if (!in_array($discountType, ['percentage', 'fixed'])) {
            $this->error('❌ Неверный тип скидки. Используйте: percentage, fixed');
            return 1;
        }

        // Проверка уникальности кода
        if (DB::table('promo_codes')->where('code', $code)->exists()) {
            $this->error("❌ Промокод '{$code}' уже существует.");
            return 1;
        }

        try {
            DB::table('promo_codes')->insert([
                'code' => strtoupper($code),
                'name' => $name,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'usage_limit' => $usageLimit,
                'used_count' => 0,
                'reusable' => false,
                'expires_at' => $expiresAt,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('🎟️ Промокод успешно создан!');
            $this->newLine();

            $this->table(
                ['Параметр', 'Значение'],
                [
                    ['Код', strtoupper($code)],
                    ['Название', $name ?: '-'],
                    ['Тип скидки', $discountType === 'percentage' ? "{$discountValue}%" : "{$discountValue} руб."],
                    ['Лимит использования', $usageLimit ?: 'Без ограничений'],
                    ['Истекает', $expiresAt ? $expiresAt->format('Y-m-d') : 'Никогда'],
                ]
            );

            $this->newLine();
            $this->info('💾 Промокод сохранен в базу данных.');
            $this->info('📋 Клиенты смогут использовать его при установке CMS.');

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при создании промокода: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Генерация лицензионного ключа
     */
    private function generateLicenseKey(): string
    {
        return strtoupper(
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8)
        );
    }

    /**
     * Проверка, является ли пользователь разработчиком
     */
    private function isDeveloper(): bool
    {
        // Проверка через .env
        if (env('DEVELOPER_MODE', false) === true || env('DEVELOPER_MODE') === 'true') {
            return true;
        }

        // Альтернативная проверка через специальный ключ
        $devKey = env('DEVELOPER_KEY');
        if ($devKey && $devKey === config('app.developer_key')) {
            return true;
        }

        // Проверка через наличие специального файла (для локальной разработки)
        if (file_exists(base_path('.developer'))) {
            return true;
        }

        return false;
    }
}

