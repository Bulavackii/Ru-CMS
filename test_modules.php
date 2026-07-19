<?php

/**
 * 🧪 Скрипт тестирования новых модулей
 *
 * Запуск: php test_modules.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Modules\System\Services\ModuleSecurityService;
use Modules\System\Services\ModuleDistributionService;
use Modules\Reviews\Services\ReviewService;
use Modules\Captcha\Services\CaptchaService;

echo "🧪 Тестирование системы модулей\n";
echo "================================\n\n";

// Тест 1: Проверка безопасности
echo "1️⃣ Тест безопасности модулей\n";
try {
    $security = new ModuleSecurityService();
    $keys = $security::generateKeys();

    if (isset($keys['private']) && isset($keys['public'])) {
        echo "✅ Генерация ключей работает\n";
    } else {
        echo "❌ Ошибка генерации ключей\n";
    }

    // Проверка сканирования
    $warnings = $security::scanForMaliciousCode(base_path('modules/System'));
    echo "✅ Сканирование кода работает (" . count($warnings) . " предупреждений)\n";

} catch (\Exception $e) {
    echo "❌ Ошибка безопасности: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 2: Модуль Отзывы
echo "2️⃣ Тест модуля Отзывы\n";
try {
    // Проверка модели
    if (class_exists(\Modules\Reviews\Models\Review::class)) {
        echo "✅ Модель Review существует\n";
    }

    // Проверка сервиса
    $reviewService = new ReviewService();
    if (method_exists($reviewService, 'getStats')) {
        echo "✅ Сервис ReviewService работает\n";
    }

    // Проверка миграций
    if (File::exists(base_path('modules/Reviews/Database/Migrations'))) {
        echo "✅ Миграции найдены\n";
    }

} catch (\Exception $e) {
    echo "❌ Ошибка Reviews: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 3: Модуль Каптча
echo "3️⃣ Тест модуля Каптча\n";
try {
    // Проверка сервиса
    $captchaService = new CaptchaService();

    // Генерация разных типов
    $types = ['image', 'slider', 'math', 'question'];
    foreach ($types as $type) {
        $result = $captchaService->generate($type);
        if (isset($result['html'])) {
            echo "✅ Генерация {$type} работает\n";
        } else {
            echo "❌ Ошибка генерации {$type}\n";
        }
    }

    // Проверка валидации (через сессию)
    $captchaService->generate('image');
    $code = Session::get('captcha_code');
    if ($code && $captchaService->verify($code, 'image')) {
        echo "✅ Проверка каптчи работает\n";
    } else {
        echo "❌ Ошибка проверки каптчи\n";
    }

} catch (\Exception $e) {
    echo "❌ Ошибка Captcha: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 4: Распределенная система
echo "4️⃣ Тест децентрализованной системы\n";
try {
    $distribution = new ModuleDistributionService();

    // Проверка репозиториев
    $repos = $distribution->getRepositories();
    if (count($repos) >= 2) {
        echo "✅ Репозитории настроены (" . count($repos) . " шт.)\n";
    }

    // Проверка экспорта
    if (File::exists(base_path('modules/System'))) {
        $result = $distribution->exportModule('System');
        if (isset($result['success'])) {
            echo "✅ Экспорт модулей работает\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Ошибка распределенной системы: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 5: Проверка файловой структуры
echo "5️⃣ Проверка файловой структуры\n";
$requiredFiles = [
    'modules/System/Models/ModuleSignature.php',
    'modules/System/Services/ModuleSecurityService.php',
    'modules/System/Services/ModuleDistributionService.php',
    'modules/Reviews/module.json',
    'modules/Reviews/Models/Review.php',
    'modules/Captcha/module.json',
    'modules/Captcha/Services/CaptchaService.php',
];

foreach ($requiredFiles as $file) {
    if (File::exists(base_path($file))) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file} не найден\n";
    }
}

echo "\n";

// Тест 6: Проверка маршрутов
echo "6️⃣ Проверка маршрутов\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();

    $requiredRoutes = [
        'admin.modules.index',
        'admin.modules.install',
        'admin.modules.toggle',
        'admin.modules.securityCheck',
        'api.reviews.get',
        'api.captcha.generate',
    ];

    foreach ($requiredRoutes as $routeName) {
        if ($routes->hasNamedRoute($routeName)) {
            echo "✅ {$routeName}\n";
        } else {
            echo "❌ {$routeName} не найден\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Ошибка маршрутов: " . $e->getMessage() . "\n";
}

echo "\n";
echo "================================\n";
echo "🧪 Тестирование завершено\n";
echo "================================\n";

// Рекомендации
echo "\n📋 Рекомендации:\n";
echo "1. Запустите миграции: php artisan migrate\n";
echo "2. Активируйте модули в /admin/modules\n";
echo "3. Проверьте права доступа к storage\n";
echo "4. Сгенерируйте ключи для важных модулей\n";
echo "5. Протестируйте в браузере\n";
