<?php

namespace Modules\Localization\Services;

use RuntimeException;

/**
 * 📝 Работа с файлами переводов (resources/lang/<locale>/<group>.php).
 *
 * Обслуживает графический редактор переводов в админке: читает языковые
 * файлы, отдаёт их «плоским» списком ключей (dot-нотация), сохраняет
 * обратно и умеет заводить новую локаль с нуля.
 *
 * Почему файлы, а не БД: переводы — часть кода, они версионируются в git
 * вместе с вьюхами, которые на них ссылаются. Редактор правит те же самые
 * файлы, что и разработчик руками, — двух источников правды не возникает.
 */
class TranslationFileService
{
    /**
     * Эталонная локаль: её набор ключей считается полным, по ней строится
     * прогресс перевода остальных языков. Совпадает с языком, на котором
     * пишется проект.
     */
    public const REFERENCE_LOCALE = 'ru';

    /** Локали, которые нельзя удалить: эталон и fallback приложения. */
    public function protectedLocales(): array
    {
        return array_values(array_unique([
            self::REFERENCE_LOCALE,
            (string) config('app.fallback_locale', 'en'),
        ]));
    }

    public function basePath(): string
    {
        return app()->langPath();
    }

    /** Все локали = подкаталоги resources/lang. */
    public function locales(): array
    {
        $dirs = glob($this->basePath() . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

        $locales = array_map('basename', $dirs);
        $locales = array_values(array_filter($locales, fn ($l) => $this->isValidLocale($l)));
        sort($locales);

        return $locales;
    }

    /**
     * Группы (файлы) локали без расширения. Без аргумента — объединение
     * групп всех локалей, чтобы в редакторе были видны и те файлы, которых
     * в текущем языке ещё нет.
     */
    public function groups(?string $locale = null): array
    {
        $locales = $locale !== null ? [$locale] : $this->locales();

        $groups = [];
        foreach ($locales as $loc) {
            if (!$this->isValidLocale($loc)) {
                continue;
            }
            $files = glob($this->localePath($loc) . DIRECTORY_SEPARATOR . '*.php') ?: [];
            foreach ($files as $file) {
                $groups[] = basename($file, '.php');
            }
        }

        $groups = array_values(array_unique($groups));
        sort($groups);

        return $groups;
    }

    /** Содержимое файла перевода как есть (вложенный массив). */
    public function load(string $locale, string $group): array
    {
        $this->assertLocale($locale);
        $this->assertGroup($group);

        $path = $this->filePath($locale, $group);
        if (!is_file($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }

    /** То же, но «плоско»: ['welcome.start' => 'Начать установку', ...]. */
    public function loadFlat(string $locale, string $group): array
    {
        return $this->flatten($this->load($locale, $group));
    }

    /**
     * Сохранение. На вход — плоский массив ключей; вложенность
     * восстанавливается по точкам. Ключи, которых нет во входных данных,
     * из файла исчезают — редактор всегда присылает полный набор.
     */
    public function save(string $locale, string $group, array $flat): void
    {
        $this->assertLocale($locale);
        $this->assertGroup($group);

        $path = $this->filePath($locale, $group);
        $dir  = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Не удалось создать каталог: {$dir}");
        }

        // Шапку файла (<?php + комментарии до return) сохраняем — в ней
        // живут пояснения разработчика, редактор не должен их стирать.
        $preamble = $this->preamble($path);
        $contents = $preamble . 'return ' . $this->export($this->unflatten($flat)) . ";\n";

        // Бэкап предыдущей версии: правки переводов легко откатить руками.
        if (is_file($path)) {
            @copy($path, $path . '.bak');
        }

        // Атомарная запись: сначала во временный файл, потом переименование.
        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
            throw new RuntimeException("Не удалось записать файл: {$path}");
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException("Не удалось заменить файл: {$path}");
        }

        $this->forgetCache();
    }

    /**
     * Прогресс перевода локали относительно эталона.
     *
     * «Не переведено» — это либо отсутствующий ключ, либо значение,
     * дословно совпадающее с эталонным (типичная ситуация сразу после
     * создания языка копированием). Совпадение — эвристика: короткие слова
     * вроде «Email» законно одинаковы в разных языках, поэтому число
     * показывает ориентир, а не точную метрику.
     */
    public function stats(string $locale): array
    {
        $reference = self::REFERENCE_LOCALE;

        $total = 0;
        $missing = 0;
        $same = 0;

        foreach ($this->groups($reference) as $group) {
            $ref = $this->loadFlat($reference, $group);
            $cur = $locale === $reference ? $ref : $this->loadFlat($locale, $group);

            foreach ($ref as $key => $refValue) {
                $total++;

                if (!array_key_exists($key, $cur) || $cur[$key] === '') {
                    $missing++;
                    continue;
                }

                if ($locale !== $reference && $cur[$key] === $refValue) {
                    $same++;
                }
            }
        }

        $translated = $total - $missing - $same;

        return [
            'total'      => $total,
            'translated' => $translated,
            'missing'    => $missing,
            'same'       => $same,
            'percent'    => $total > 0 ? (int) round($translated / $total * 100) : 0,
            'reference'  => $locale === $reference,
        ];
    }

    /** Прогресс по одной группе — для списка файлов в редакторе. */
    public function groupStats(string $locale, string $group): array
    {
        $reference = self::REFERENCE_LOCALE;

        $ref = $this->loadFlat($reference, $group);
        $cur = $locale === $reference ? $ref : $this->loadFlat($locale, $group);

        $total = count($ref);
        $missing = 0;
        $same = 0;

        foreach ($ref as $key => $refValue) {
            if (!array_key_exists($key, $cur) || $cur[$key] === '') {
                $missing++;
                continue;
            }
            if ($locale !== $reference && $cur[$key] === $refValue) {
                $same++;
            }
        }

        $translated = $total - $missing - $same;

        return [
            'total'      => $total,
            'translated' => $translated,
            'missing'    => $missing,
            'same'       => $same,
            'percent'    => $total > 0 ? (int) round($translated / $total * 100) : 0,
        ];
    }

    /**
     * Создание новой локали копированием из существующей.
     *
     * Значения копируются как есть (а не пустыми): так интерфейс сразу
     * работает, а в редакторе видно, что именно предстоит перевести.
     */
    public function createLocale(string $code, string $copyFrom): void
    {
        $this->assertLocale($code);
        $this->assertLocale($copyFrom);

        $target = $this->localePath($code);
        if (is_dir($target)) {
            throw new RuntimeException("Локаль «{$code}» уже существует.");
        }

        if (!mkdir($target, 0775, true) && !is_dir($target)) {
            throw new RuntimeException("Не удалось создать каталог: {$target}");
        }

        foreach ($this->groups($copyFrom) as $group) {
            $source = $this->filePath($copyFrom, $group);
            if (is_file($source)) {
                copy($source, $this->filePath($code, $group));
            }
        }

        $this->forgetCache();
    }

    /** Удаление локали целиком. Эталон и fallback защищены. */
    public function deleteLocale(string $code): void
    {
        $this->assertLocale($code);

        if (in_array($code, $this->protectedLocales(), true)) {
            throw new RuntimeException("Локаль «{$code}» защищена от удаления.");
        }

        $dir = $this->localePath($code);
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);

        $this->forgetCache();
    }

    // ─────────────────────────────────────────────────────────────────────
    // ВСПОМОГАТЕЛЬНЫЕ
    // ─────────────────────────────────────────────────────────────────────

    public function isValidLocale(string $locale): bool
    {
        // ru, en, be, kk, pt_BR, zh-Hans — но никаких слэшей и точек,
        // иначе через код локали можно выйти за пределы resources/lang.
        return (bool) preg_match('/^[a-z]{2,3}([_-][A-Za-z]{2,8})?$/', $locale);
    }

    public function isValidGroup(string $group): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/i', $group);
    }

    private function assertLocale(string $locale): void
    {
        if (!$this->isValidLocale($locale)) {
            throw new RuntimeException("Недопустимый код локали: «{$locale}».");
        }
    }

    private function assertGroup(string $group): void
    {
        if (!$this->isValidGroup($group)) {
            throw new RuntimeException("Недопустимое имя файла переводов: «{$group}».");
        }
    }

    private function localePath(string $locale): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . $locale;
    }

    private function filePath(string $locale, string $group): string
    {
        return $this->localePath($locale) . DIRECTORY_SEPARATOR . $group . '.php';
    }

    /** Вложенный массив → плоский с ключами через точку. */
    public function flatten(array $data, string $prefix = ''): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $out += $this->flatten($value, $full);
            } else {
                $out[$full] = (string) $value;
            }
        }

        return $out;
    }

    /** Плоский массив → вложенный. */
    public function unflatten(array $flat): array
    {
        $out = [];

        foreach ($flat as $key => $value) {
            $parts = explode('.', (string) $key);
            $node = &$out;

            foreach ($parts as $part) {
                // Числовые сегменты возвращаем в int-ключи, чтобы списки
                // (например strength_levels) снова стали списками.
                $part = ctype_digit($part) ? (int) $part : $part;

                if (!isset($node[$part]) || !is_array($node[$part])) {
                    $node[$part] = [];
                }
                $node = &$node[$part];
            }

            $node = $value;
            unset($node);
        }

        return $out;
    }

    /** Массив → отформатированный PHP-код. */
    private function export(array $data, int $depth = 1): string
    {
        $pad = str_repeat('    ', $depth);
        $isList = array_is_list($data);

        $out = "[\n";
        foreach ($data as $key => $value) {
            $out .= $pad;

            if (!$isList) {
                $out .= is_int($key) ? $key . ' => ' : $this->quote((string) $key) . ' => ';
            }

            $out .= is_array($value)
                ? $this->export($value, $depth + 1)
                : $this->quote((string) $value);

            $out .= ",\n";
        }
        $out .= str_repeat('    ', max($depth - 1, 0)) . ']';

        return $out;
    }

    /** Строка в одинарных кавычках с экранированием \ и '. */
    private function quote(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

    /**
     * Шапка файла: всё до `return`. Сохраняем её при перезаписи, чтобы
     * не потерять `<?php` и комментарии-пояснения к словарю.
     */
    private function preamble(string $path): string
    {
        $default = "<?php\n\n";

        if (!is_file($path)) {
            return $default;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return $default;
        }

        $pos = strpos($raw, 'return');

        return $pos === false ? $default : substr($raw, 0, $pos);
    }

    /** Сброс кеша переводов, чтобы правки применились сразу. */
    private function forgetCache(): void
    {
        try {
            app('translator')->setLoaded([]);
        } catch (\Throwable $e) {
            // Не критично: переводы всё равно перечитаются на следующем запросе.
        }
    }
}
