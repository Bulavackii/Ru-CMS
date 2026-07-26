<?php

namespace Modules\Localization\Console\Commands;

use Illuminate\Console\Command;

/**
 * 🔤 Проверка полноты словарей перевода.
 *
 *   php artisan lang:check          # отчёт по всем локалям
 *   php artisan lang:check --group=frontend
 *
 * Русский — эталон: структура ключей задаётся им, остальные локали обязаны
 * повторять её один в один. Команда показывает, каких ключей не хватает и
 * какие лишние, и возвращает ненулевой код, если расхождения есть, — так её
 * можно поставить в CI.
 */
class CheckDictionariesCommand extends Command
{
    protected $signature = 'lang:check {--group= : Проверить только один файл словаря}';

    protected $description = 'Сверяет словари всех локалей с эталонным русским';

    /** Локаль-эталон */
    private const REFERENCE = 'ru';

    public function handle(): int
    {
        $langPath = app()->langPath();
        $groups = $this->groups($langPath);

        if ($groups === []) {
            $this->warn('Словари не найдены: ' . $langPath);

            return self::SUCCESS;
        }

        $locales = array_values(array_diff($this->locales($langPath), [self::REFERENCE]));
        $problems = 0;

        foreach ($groups as $group) {
            $reference = $this->keys($langPath, self::REFERENCE, $group);

            if ($reference === []) {
                continue;
            }

            $rows = [];

            foreach ($locales as $locale) {
                $keys = $this->keys($langPath, $locale, $group);
                $missing = array_diff($reference, $keys);
                $extra = array_diff($keys, $reference);
                $percent = (int) round(count(array_intersect($reference, $keys)) / count($reference) * 100);

                if ($missing || $extra) {
                    $problems++;
                }

                $rows[] = [
                    $locale,
                    count($keys) . ' / ' . count($reference),
                    $percent . '%',
                    count($missing) ?: '—',
                    count($extra) ?: '—',
                ];
            }

            $this->line('');
            $this->info("Словарь: {$group}.php");
            $this->table(['Локаль', 'Ключей', 'Готовность', 'Не хватает', 'Лишних'], $rows);

            if ($this->output->isVerbose()) {
                foreach ($locales as $locale) {
                    $missing = array_diff($reference, $this->keys($langPath, $locale, $group));
                    if ($missing) {
                        $this->line("  {$locale}: " . implode(', ', array_slice($missing, 0, 20))
                            . (count($missing) > 20 ? ' …' : ''));
                    }
                }
            }
        }

        if ($problems > 0) {
            $this->line('');
            $this->warn("Расхождений: {$problems}. Запустите с -v, чтобы увидеть недостающие ключи.");

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Все словари совпадают с эталоном.');

        return self::SUCCESS;
    }

    /** Список локалей (каталоги вида ru, en, pt_BR) */
    private function locales(string $langPath): array
    {
        $dirs = glob($langPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

        return array_values(array_filter(
            array_map('basename', $dirs),
            fn ($code) => (bool) preg_match('~^[a-z]{2}([_-][A-Za-z0-9]+)?$~', $code)
        ));
    }

    /** Файлы словарей эталона (или один, если задан --group) */
    private function groups(string $langPath): array
    {
        if ($group = $this->option('group')) {
            return [$group];
        }

        $files = glob($langPath . DIRECTORY_SEPARATOR . self::REFERENCE . DIRECTORY_SEPARATOR . '*.php') ?: [];

        return array_map(fn ($file) => basename($file, '.php'), $files);
    }

    /** Плоский список ключей словаря в dot-нотации */
    private function keys(string $langPath, string $locale, string $group): array
    {
        $file = $langPath . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $group . '.php';

        if (! is_file($file)) {
            return [];
        }

        $data = include $file;

        return is_array($data) ? $this->flatten($data) : [];
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $keys = [];

        foreach ($data as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $keys = is_array($value)
                ? array_merge($keys, $this->flatten($value, $full))
                : array_merge($keys, [$full]);
        }

        return $keys;
    }
}
