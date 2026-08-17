<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 🔴 База данных — только PostgreSQL.
 *
 * Железное правило проекта: мастер установки не предлагает другие драйверы, а
 * обслуживание (бэкап через `pg_dump`, `VACUUM ANALYZE`) рассчитано на него.
 * Раньше правило жило только на словах: `install.sh` ставил MySQL, документация
 * требовала «MySQL 8.0+», а `config/database.php` называл MySQL «основным
 * вариантом для продакшена» — то есть новая установка по инструкции приводила к
 * нерабочему сайту.
 */
class PostgresOnlyTest extends TestCase
{
    /**
     * Приложение падает с внятной ошибкой на чужом драйвере.
     *
     * ⚠️ Падаем ЯВНО, а не переключаемся на pgsql молча: подмена за спиной
     * привела бы к попытке работать с чужой базой по чужим реквизитам.
     */
    public function test_foreign_driver_is_refused_loudly(): void
    {
        $провайдер = new \App\Providers\AppServiceProvider($this->app);

        $метод = new \ReflectionMethod($провайдер, 'guardDatabaseDriver');
        $метод->setAccessible(true);

        config(['database.default' => 'mysql']);

        try {
            $метод->invoke($провайдер);
            $this->fail('Чужой драйвер прошёл без возражений');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('только на PostgreSQL', $e->getMessage());
            // В сообщении должно быть сказано, ЧТО делать
            $this->assertStringContainsString('DB_CONNECTION=pgsql', $e->getMessage());
        }
    }

    /** А pgsql и sqlite проходят: на втором гоняются тесты. */
    public function test_pgsql_and_sqlite_are_allowed(): void
    {
        $провайдер = new \App\Providers\AppServiceProvider($this->app);
        $метод = new \ReflectionMethod($провайдер, 'guardDatabaseDriver');
        $метод->setAccessible(true);

        foreach (['pgsql', 'sqlite'] as $соединение) {
            config(['database.default' => $соединение]);
            $метод->invoke($провайдер);
        }

        $this->assertTrue(true, 'Оба разрешённых драйвера прошли');
    }

    /**
     * Драйвер по умолчанию — pgsql.
     *
     * Стоял `sqlite`: при пустом DB_CONNECTION приложение молча уходило в
     * файловую базу вместо настоящей.
     */
    public function test_default_driver_is_pgsql(): void
    {
        $файл = file_get_contents(config_path('database.php'));

        $this->assertStringContainsString(
            "'default' => env('DB_CONNECTION', 'pgsql')",
            $файл,
            'Драйвер по умолчанию снова не pgsql'
        );
    }

    /**
     * В конфиге не описаны чужие драйверы.
     *
     * ⚠️ Из РАБОТАЮЩЕГО приложения они при этом не исчезают: Laravel 11+
     * сливает `database.connections` со своими значениями по умолчанию, и
     * mysql с mariadb возвращаются оттуда. Поэтому проверяем именно файл — он
     * не должен приглашать их настроить, — а запрет обеспечивает заслон выше.
     */
    public function test_config_does_not_describe_foreign_drivers(): void
    {
        $файл = file_get_contents(config_path('database.php'));

        preg_match('~\'connections\' => \[(.+?)\n    \],~s', $файл, $m);
        $блок = $m[1] ?? $файл;

        foreach (['mysql', 'mariadb', 'sqlsrv'] as $драйвер) {
            $this->assertStringNotContainsString(
                "'{$драйвер}' => [",
                $блок,
                "В конфиге снова описан драйвер {$драйвер}"
            );
        }
    }

    /** Бэкап и обслуживание таблиц — по-постгресовому. */
    public function test_maintenance_targets_postgres(): void
    {
        $бэкап = file_get_contents(base_path('app/Jobs/BackupDatabase.php'));

        $this->assertStringContainsString('pg_dump', $бэкап);
        $this->assertStringNotContainsString('mysqldump', $бэкап, 'Вернулась ветка бэкапа MySQL');

        $оптимизация = file_get_contents(base_path('app/Console/Commands/OptimizePerformance.php'));

        // Раньше здесь стоял OPTIMIZE TABLE под условием «если драйвер mysql»,
        // то есть на этом проекте ветка не выполнялась НИКОГДА.
        $this->assertStringContainsString('VACUUM ANALYZE', $оптимизация);
        $this->assertStringNotContainsString('OPTIMIZE TABLE', $оптимизация);
    }

    /**
     * Скрипт установки и документация не ведут в MySQL.
     *
     * Это половина ответа на вопрос «чтобы работало после любой новой
     * установки»: инструкция, ведущая в чужую базу, ломает установку надёжнее
     * любой ошибки в коде.
     */
    public function test_installer_and_docs_do_not_mention_mysql(): void
    {
        foreach (['install.sh', 'docs/INSTALLATION.md'] as $файл) {
            $текст = file_get_contents(base_path($файл));

            // Убираем строки-пояснения «раньше здесь стоял mysql»: они
            // рассказывают историю, а не предлагают его настроить.
            $строки = array_filter(
                preg_split('~\R~', $текст),
                fn ($с) => ! preg_match('~^\s*(#|//|\*|>)~', $с) && ! str_contains($с, 'Раньше')
            );

            $this->assertDoesNotMatchRegularExpression(
                '~mysql|mariadb~i',
                implode("\n", $строки),
                "{$файл} снова ведёт установку в MySQL"
            );
        }
    }

    /**
     * Пример окружения есть в репозитории и настроен на PostgreSQL.
     *
     * ⚠️ Файла `.env.example` в репозитории НЕ БЫЛО вовсе, хотя и COMMANDS.md,
     * и install.sh начинали с `cp .env.example .env` — то есть первый шаг
     * documented-установки не работал после свежего клонирования.
     */
    public function test_env_example_exists_and_targets_postgres(): void
    {
        $путь = base_path('.env.example');

        $this->assertFileExists($путь, 'Пропал .env.example — установка по инструкции сломается');

        $текст = file_get_contents($путь);

        $this->assertStringContainsString('DB_CONNECTION=pgsql', $текст);
        $this->assertStringContainsString('DB_PORT=5432', $текст);
        $this->assertDoesNotMatchRegularExpression('~DB_CONNECTION=(mysql|mariadb)~', $текст);

        // Несущие ключи, без которых установка спотыкается
        foreach (['APP_KEY', 'MAIL_TIMEOUT', 'DB_DATABASE', 'DB_USERNAME'] as $ключ) {
            $this->assertStringContainsString($ключ . '=', $текст, "В примере нет ключа {$ключ}");
        }
    }
}
