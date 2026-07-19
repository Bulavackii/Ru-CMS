<?php

namespace Modules\Localization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Localization\Models\Country;
use Modules\Localization\Models\LocalizationSetting;
use Modules\Localization\Services\LocalizationService;

class LocalizationSeeder extends Seeder
{
    public function run(): void
    {
        $service = new LocalizationService();

        // Импорт предустановленных стран
        $presets = config('localization.preset_countries', []);

        // Переводы для каждой страны
        $translations = [
            'RU' => [
                'welcome' => 'Добро пожаловать',
                'home' => 'Главная',
                'about' => 'О нас',
                'contact' => 'Контакты',
                'price' => 'Цена',
                'date' => 'Дата',
                'time' => 'Время',
            ],
            'KZ' => [
                'welcome' => 'Қош келдіңіз',
                'home' => 'Басты',
                'about' => 'Біз туралы',
                'contact' => 'Байланыс',
                'price' => 'Баға',
                'date' => 'Күні',
                'time' => 'Уақыты',
            ],
            'US' => [
                'welcome' => 'Welcome',
                'home' => 'Home',
                'about' => 'About',
                'contact' => 'Contact',
                'price' => 'Price',
                'date' => 'Date',
                'time' => 'Time',
            ],
            'GB' => [
                'welcome' => 'Welcome',
                'home' => 'Home',
                'about' => 'About',
                'contact' => 'Contact',
                'price' => 'Price',
                'date' => 'Date',
                'time' => 'Time',
            ],
            'DE' => [
                'welcome' => 'Willkommen',
                'home' => 'Startseite',
                'about' => 'Über uns',
                'contact' => 'Kontakt',
                'price' => 'Preis',
                'date' => 'Datum',
                'time' => 'Zeit',
            ],
            'FR' => [
                'welcome' => 'Bienvenue',
                'home' => 'Accueil',
                'about' => 'À propos',
                'contact' => 'Contact',
                'price' => 'Prix',
                'date' => 'Date',
                'time' => 'Heure',
            ],
            'IT' => [
                'welcome' => 'Benvenuto',
                'home' => 'Casa',
                'about' => 'Chi siamo',
                'contact' => 'Contatti',
                'price' => 'Prezzo',
                'date' => 'Data',
                'time' => 'Ora',
            ],
        ];

        foreach ($presets as $code => $data) {
            $country = $service->createOrUpdateCountry(array_merge(['code' => $code], $data));

            // Добавляем системные настройки
            LocalizationSetting::set($country->id, 'welcome_message', $translations[$code]['welcome'] ?? "Welcome to {$country->name}!", 'string', 'translation', 'Приветственное сообщение');
            LocalizationSetting::set($country->id, 'week_start', '1', 'number', 'date', 'Первый день недели (1=Понедельник)');
            
            // Налоговые ставки по странам
            $taxRates = [
                'RU' => '20',
                'KZ' => '12',
                'US' => '0', // Зависит от штата
                'GB' => '20',
                'DE' => '19',
                'FR' => '20',
                'IT' => '22',
            ];
            LocalizationSetting::set($country->id, 'tax_rate', $taxRates[$code] ?? '20', 'number', 'currency', 'Ставка налога по умолчанию (%)');
            
            // Позиция валюты
            $currencyPositions = [
                'RU' => 'after',
                'KZ' => 'after',
                'US' => 'before',
                'GB' => 'before',
                'DE' => 'after',
                'FR' => 'after',
                'IT' => 'after',
            ];
            LocalizationSetting::set($country->id, 'currency_position', $currencyPositions[$code] ?? 'after', 'string', 'currency', 'Позиция символа валюты');
            
            // Сохраняем переводы
            if (isset($translations[$code])) {
                LocalizationSetting::set($country->id, 'translations', $translations[$code], 'json', 'translation', 'Базовые переводы');
            }

            $this->command->info("✅ Импортирована страна: {$country->flag} {$country->name} ({$code}) - {$country->locale}");
        }

        $this->command->info("\n✅ Модуль Localization успешно установлен!");
        $this->command->info("🌍 Страны: " . Country::count());
        $this->command->info("⚙️ Настройки: " . LocalizationSetting::count());
        $this->command->info("\n📝 Созданы предустановленные страны:");
        $this->command->info("   🇷🇺 Россия (RU) - ru");
        $this->command->info("   🇰🇿 Казахстан (KZ) - kk");
        $this->command->info("   🇺🇸 США (US) - en");
        $this->command->info("   🇬🇧 Великобритания (GB) - en");
        $this->command->info("   🇩🇪 Германия (DE) - de");
        $this->command->info("   🇫🇷 Франция (FR) - fr");
        $this->command->info("   🇮🇹 Италия (IT) - it");
        $this->command->info("\n🔗 Админка: /admin/localization");
        $this->command->info("🔗 API: /localization/frontend-data");
    }
}
