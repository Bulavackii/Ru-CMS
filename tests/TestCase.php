<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Настройка для тестов
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Система считается установленной. Раньше тесты требовали НАСТОЯЩИЙ
        // storage/install.lock, и прогон в момент, когда владелец проходит
        // мастер, заставлял создавать этот файл в его рабочем каталоге —
        // установка сбрасывалась на середине. Теперь путь свой (phpunit.xml,
        // INSTALL_LOCK_PATH), и боевой замок никто не трогает.
        $this->markInstalled();
    }

    /** Завести файл-замок в тестовом каталоге. */
    protected function markInstalled(): void
    {
        $путь = install_lock_path();
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($путь));
        \Illuminate\Support\Facades\File::put($путь, 'Installed (test)');
    }

    /**
     * Убрать замок — для проверок мастера установки: он открыт только пока
     * система НЕ установлена.
     */
    protected function markNotInstalled(): void
    {
        \Illuminate\Support\Facades\File::delete(install_lock_path());
    }
}
