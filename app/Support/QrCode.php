<?php

namespace App\Support;

/**
 * 🔳 Генератор QR-кода — свой, без сторонних библиотек.
 *
 * Нужен для привязки двухфакторной проверки: на странице настройки в тег
 * картинки подставлялась СТРОКА otpauth://… — это содержимое кода, а не
 * изображение, и браузер показывал пустую рамку. Отсканировать было нечего.
 *
 * Почему свой, а не библиотека. Владелец продаёт CMS закрытым продуктом и
 * просил обойтись без новых зависимостей. Рисование QR целиком локальное:
 * ни один вариант никуда не обращается, разница только в том, чей это код.
 *
 * Что реализовано (ISO/IEC 18004): байтовый режим, уровень коррекции M,
 * версии с 1 по 10 — до 213 байт полезных данных, чего с запасом хватает
 * строке привязки (около 130 символов). Все восемь масок с выбором лучшей
 * по штрафным очкам, как требует стандарт: без этого код может не
 * прочитаться из-за неудачного узора.
 *
 * Вывод — SVG: он не зависит от расширений PHP (ни GD, ни Imagick),
 * не мылится при увеличении и вставляется прямо в страницу.
 */
class QrCode
{
    /** Уровень коррекции ошибок M — компромисс объёма и надёжности. */
    private const EC_LEVEL_BITS = 0b00;

    /**
     * Параметры версий: [всего кодовых слов, слов коррекции на блок,
     * блоков группы 1, данных в блоке группы 1, блоков группы 2,
     * данных в блоке группы 2].
     */
    private const VERSIONS = [
        1  => [26, 10, 1, 16, 0, 0],
        2  => [44, 16, 1, 28, 0, 0],
        3  => [70, 26, 1, 44, 0, 0],
        4  => [100, 18, 2, 32, 0, 0],
        5  => [134, 24, 2, 43, 0, 0],
        6  => [172, 16, 4, 27, 0, 0],
        7  => [196, 18, 4, 31, 0, 0],
        8  => [242, 22, 2, 38, 2, 39],
        9  => [292, 22, 3, 36, 2, 37],
        10 => [346, 26, 4, 43, 1, 44],
    ];

    /** Координаты центров совмещающих узоров по версиям. */
    private const ALIGNMENT = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /** @var int[] Таблицы логарифмов поля Галуа GF(256) */
    private static array $expTable = [];
    private static array $logTable = [];

    /** @var array<int, array<int, int>> Матрица модулей: 1 — тёмный, 0 — светлый */
    private array $matrix = [];

    /** @var array<int, array<int, bool>> Служебные модули, которые маска не трогает */
    private array $reserved = [];

    private int $version;
    private int $size;

    /**
     * Нарисовать код и вернуть готовый SVG.
     *
     * @param  string  $text    Что закодировать
     * @param  int     $scale   Размер модуля в пикселях вывода
     * @param  int     $margin  Тихая зона в модулях; стандарт требует не менее 4
     */
    public static function svg(string $text, int $scale = 6, int $margin = 4): string
    {
        return (new self())->render($text, $scale, $margin);
    }

    /** SVG в виде data-адреса — удобно подставлять прямо в тег картинки. */
    public static function dataUri(string $text, int $scale = 6, int $margin = 4): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($text, $scale, $margin));
    }

    /**
     * Матрица модулей — для проверок и тестов.
     *
     * @return array<int, array<int, int>>
     */
    public static function matrix(string $text): array
    {
        $qr = new self();
        $qr->build($text);

        return $qr->matrix;
    }

    private function render(string $text, int $scale, int $margin): string
    {
        $this->build($text);

        $side = ($this->size + $margin * 2) * $scale;
        $parts = [];

        // Модули рисуются прямоугольниками. Соседние тёмные в строке
        // объединяются в один прямоугольник: иначе на версии 8 вышло бы под
        // полторы тысячи узлов, и разметка распухла бы без всякой пользы.
        for ($row = 0; $row < $this->size; $row++) {
            $col = 0;

            while ($col < $this->size) {
                if ($this->matrix[$row][$col] !== 1) {
                    $col++;
                    continue;
                }

                $run = 0;

                while ($col + $run < $this->size && $this->matrix[$row][$col + $run] === 1) {
                    $run++;
                }

                $parts[] = sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d"/>',
                    ($col + $margin) * $scale,
                    ($row + $margin) * $scale,
                    $run * $scale,
                    $scale
                );

                $col += $run;
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $side . '" height="' . $side . '" '
            . 'viewBox="0 0 ' . $side . ' ' . $side . '" shape-rendering="crispEdges">'
            . '<rect width="' . $side . '" height="' . $side . '" fill="#ffffff"/>'
            . '<g fill="#000000">' . implode('', $parts) . '</g>'
            . '</svg>';
    }

    private function build(string $text): void
    {
        $this->version = $this->pickVersion($text);
        $this->size = 17 + $this->version * 4;

        $codewords = $this->encode($text);
        $final = $this->interleave($codewords);

        $this->prepareMatrix();
        $this->placeData($final);
        $this->applyBestMask();
    }

    /** Наименьшая версия, в которую влезает текст. */
    private function pickVersion(string $text): int
    {
        $length = strlen($text);

        foreach (array_keys(self::VERSIONS) as $version) {
            if ($length <= $this->dataCapacity($version)) {
                return $version;
            }
        }

        throw new \InvalidArgumentException(
            'Текст длиной ' . $length . ' байт не помещается в версии до 10. '
            . 'Для строки привязки двухфакторной проверки этого хватает с запасом.'
        );
    }

    /** Сколько байт данных вмещает версия с учётом служебных битов. */
    private function dataCapacity(int $version): int
    {
        [, $ecPerBlock, $g1Blocks, $g1Words, $g2Blocks, $g2Words] = self::VERSIONS[$version];

        $dataWords = $g1Blocks * $g1Words + $g2Blocks * $g2Words;

        // 4 бита режима + счётчик длины (8 бит до версии 9, дальше 16).
        $overheadBits = 4 + ($version >= 10 ? 16 : 8);

        return $dataWords - (int) ceil($overheadBits / 8);
    }

    /**
     * Байтовый режим: режим, длина, данные, заполнение до конца.
     *
     * @return int[] Кодовые слова данных
     */
    private function encode(string $text): array
    {
        $bits = '';
        $bits .= '0100'; // байтовый режим

        $countBits = $this->version >= 10 ? 16 : 8;
        $bits .= str_pad(decbin(strlen($text)), $countBits, '0', STR_PAD_LEFT);

        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        [, $ecPerBlock, $g1Blocks, $g1Words, $g2Blocks, $g2Words] = self::VERSIONS[$this->version];
        $totalDataWords = $g1Blocks * $g1Words + $g2Blocks * $g2Words;
        $capacityBits = $totalDataWords * 8;

        // Завершитель — до четырёх нулей, но не больше, чем осталось места.
        $bits .= str_repeat('0', min(4, $capacityBits - strlen($bits)));

        // Дополняем до целого байта.
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - strlen($bits) % 8);
        }

        // Добиваем чередующимися байтами, как предписывает стандарт.
        $padding = [0xEC, 0x11];
        $i = 0;

        while (strlen($bits) < $capacityBits) {
            $bits .= str_pad(decbin($padding[$i % 2]), 8, '0', STR_PAD_LEFT);
            $i++;
        }

        $words = [];

        foreach (str_split($bits, 8) as $byte) {
            $words[] = bindec($byte);
        }

        return $words;
    }

    /**
     * Разбить на блоки, посчитать коррекцию и перемешать, как требует
     * стандарт: слова блоков идут по очереди, иначе царапина уничтожит
     * целый блок вместо равномерных потерь по всем.
     *
     * @param  int[]  $dataWords
     * @return int[]
     */
    private function interleave(array $dataWords): array
    {
        [, $ecPerBlock, $g1Blocks, $g1Words, $g2Blocks, $g2Words] = self::VERSIONS[$this->version];

        $blocks = [];
        $ecBlocks = [];
        $offset = 0;

        foreach ([[$g1Blocks, $g1Words], [$g2Blocks, $g2Words]] as [$count, $words]) {
            for ($i = 0; $i < $count; $i++) {
                $block = array_slice($dataWords, $offset, $words);
                $offset += $words;

                $blocks[] = $block;
                $ecBlocks[] = $this->reedSolomon($block, $ecPerBlock);
            }
        }

        $result = [];
        $longest = max(array_map('count', $blocks));

        for ($i = 0; $i < $longest; $i++) {
            foreach ($blocks as $block) {
                if (isset($block[$i])) {
                    $result[] = $block[$i];
                }
            }
        }

        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                if (isset($block[$i])) {
                    $result[] = $block[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Слова коррекции Рида — Соломона в поле GF(256).
     *
     * @param  int[]  $data
     * @return int[]
     */
    private function reedSolomon(array $data, int $ecCount): array
    {
        self::initGaloisField();

        // Порождающий многочлен: произведение (x - a^i) для i от 0 до ecCount-1.
        $generator = [1];

        for ($i = 0; $i < $ecCount; $i++) {
            $next = array_fill(0, count($generator) + 1, 0);

            foreach ($generator as $index => $coefficient) {
                $next[$index] ^= $coefficient;
                $next[$index + 1] ^= self::gfMultiply($coefficient, self::$expTable[$i]);
            }

            $generator = $next;
        }

        $remainder = array_merge($data, array_fill(0, $ecCount, 0));

        for ($i = 0; $i < count($data); $i++) {
            $factor = $remainder[$i];

            if ($factor === 0) {
                continue;
            }

            foreach ($generator as $index => $coefficient) {
                $remainder[$i + $index] ^= self::gfMultiply($coefficient, $factor);
            }
        }

        return array_slice($remainder, count($data), $ecCount);
    }

    private static function initGaloisField(): void
    {
        if (self::$expTable !== []) {
            return;
        }

        $value = 1;

        for ($i = 0; $i < 256; $i++) {
            self::$expTable[$i] = $value;
            self::$logTable[$value] = $i;

            $value <<= 1;

            // Примитивный многочлен поля: x^8 + x^4 + x^3 + x^2 + 1.
            if ($value & 0x100) {
                $value ^= 0x11D;
            }
        }
    }

    private static function gfMultiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$expTable[(self::$logTable[$a] + self::$logTable[$b]) % 255];
    }

    /** Служебные узоры: поисковые, синхронизации, совмещающие, тёмный модуль. */
    private function prepareMatrix(): void
    {
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                $this->matrix[$row][$col] = 0;
                $this->reserved[$row][$col] = false;
            }
        }

        foreach ([[0, 0], [0, $this->size - 7], [$this->size - 7, 0]] as [$row, $col]) {
            $this->placeFinder($row, $col);
        }

        // Полосы синхронизации задают шаг сетки.
        for ($i = 8; $i < $this->size - 8; $i++) {
            $bit = $i % 2 === 0 ? 1 : 0;
            $this->set(6, $i, $bit, true);
            $this->set($i, 6, $bit, true);
        }

        $this->placeAlignment();

        // Тёмный модуль — обязателен по стандарту.
        $this->set($this->size - 8, 8, 1, true);

        $this->reserveFormatArea();

        if ($this->version >= 7) {
            $this->placeVersionInfo();
        }
    }

    private function placeFinder(int $top, int $left): void
    {
        // Сам узор 7x7 плюс светлая рамка вокруг: без неё сканер не отличит
        // угол кода от соседнего содержимого.
        for ($row = -1; $row <= 7; $row++) {
            for ($col = -1; $col <= 7; $col++) {
                $r = $top + $row;
                $c = $left + $col;

                if ($r < 0 || $r >= $this->size || $c < 0 || $c >= $this->size) {
                    continue;
                }

                $inner = $row >= 0 && $row <= 6 && $col >= 0 && $col <= 6;
                $ring = $row === 0 || $row === 6 || $col === 0 || $col === 6;
                $core = $row >= 2 && $row <= 4 && $col >= 2 && $col <= 4;

                $this->set($r, $c, $inner && ($ring || $core) ? 1 : 0, true);
            }
        }
    }

    private function placeAlignment(): void
    {
        $centers = self::ALIGNMENT[$this->version];

        if ($centers === []) {
            return;
        }

        $first = $centers[0];
        $last = $centers[count($centers) - 1];

        foreach ($centers as $row) {
            foreach ($centers as $col) {
                // Пропускаем ровно три угла, занятых поисковыми узорами.
                // Проверять «ячейка уже служебная» здесь нельзя: центры вида
                // (6, N) стоят НА полосе синхронизации, которая размечена
                // раньше, и такой узор молча не рисовался бы — с версии 7,
                // где эти центры появляются, код переставал читаться.
                $corner = ($row === $first && $col === $first)
                    || ($row === $first && $col === $last)
                    || ($row === $last && $col === $first);

                if ($corner) {
                    continue;
                }

                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $edge = abs($dr) === 2 || abs($dc) === 2;
                        $center = $dr === 0 && $dc === 0;
                        $this->set($row + $dr, $col + $dc, $edge || $center ? 1 : 0, true);
                    }
                }
            }
        }
    }

    /** Места под сведения о формате помечаем занятыми — заполним после маски. */
    private function reserveFormatArea(): void
    {
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $this->reserved[8][$i] = true;
                $this->reserved[$i][8] = true;
            }
        }

        for ($i = 0; $i < 8; $i++) {
            $this->reserved[8][$this->size - 1 - $i] = true;
            $this->reserved[$this->size - 1 - $i][8] = true;
        }
    }

    /** Сведения о версии — только для версий от 7 и выше. */
    private function placeVersionInfo(): void
    {
        $value = $this->version << 12;
        $remainder = $value;

        for ($i = 0; $i < 6; $i++) {
            if ($remainder & (1 << (17 - $i))) {
                $remainder ^= 0x1F25 << (5 - $i);
            }
        }

        $bits = $value | $remainder;

        for ($i = 0; $i < 18; $i++) {
            $bit = ($bits >> $i) & 1;
            $row = intdiv($i, 3);
            $col = $this->size - 11 + ($i % 3);

            $this->set($row, $col, $bit, true);
            $this->set($col, $row, $bit, true);
        }
    }

    /**
     * Разложить данные змейкой снизу вверх по два столбца, обходя служебное.
     *
     * @param  int[]  $words
     */
    private function placeData(array $words): void
    {
        $bits = '';

        foreach ($words as $word) {
            $bits .= str_pad(decbin($word), 8, '0', STR_PAD_LEFT);
        }

        $index = 0;
        $upward = true;

        for ($right = $this->size - 1; $right > 0; $right -= 2) {
            // Шестой столбец занят полосой синхронизации — пропускаем его.
            if ($right === 6) {
                $right = 5;
            }

            for ($step = 0; $step < $this->size; $step++) {
                $row = $upward ? $this->size - 1 - $step : $step;

                foreach ([$right, $right - 1] as $col) {
                    if ($this->reserved[$row][$col]) {
                        continue;
                    }

                    $this->matrix[$row][$col] = $index < strlen($bits) ? (int) $bits[$index] : 0;
                    $index++;
                }
            }

            $upward = ! $upward;
        }
    }

    /**
     * Выбрать маску с наименьшим штрафом.
     *
     * Стандарт требует перебрать все восемь: неудачный узор (сплошные полосы,
     * ложные поисковые узоры) сканер прочитать не сможет.
     */
    private function applyBestMask(): void
    {
        $clean = $this->matrix;
        $best = null;
        $bestPenalty = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $this->matrix = $clean;
            $this->maskWith($mask);
            $this->placeFormatInfo($mask);

            $penalty = $this->penalty();

            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $best = $this->matrix;
            }
        }

        $this->matrix = $best;
    }

    private function maskWith(int $mask): void
    {
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if ($this->reserved[$row][$col]) {
                    continue;
                }

                if ($this->maskCondition($mask, $row, $col)) {
                    $this->matrix[$row][$col] ^= 1;
                }
            }
        }
    }

    private function maskCondition(int $mask, int $row, int $col): bool
    {
        return match ($mask) {
            0 => ($row + $col) % 2 === 0,
            1 => $row % 2 === 0,
            2 => $col % 3 === 0,
            3 => ($row + $col) % 3 === 0,
            4 => (intdiv($row, 2) + intdiv($col, 3)) % 2 === 0,
            5 => ($row * $col) % 2 + ($row * $col) % 3 === 0,
            6 => (($row * $col) % 2 + ($row * $col) % 3) % 2 === 0,
            7 => ((($row + $col) % 2) + ($row * $col) % 3) % 2 === 0,
        };
    }

    /** Сведения о формате: уровень коррекции и номер маски, защищённые BCH. */
    private function placeFormatInfo(int $mask): void
    {
        $data = (self::EC_LEVEL_BITS << 3) | $mask;
        $remainder = $data << 10;

        for ($i = 0; $i < 5; $i++) {
            if ($remainder & (1 << (14 - $i))) {
                $remainder ^= 0x537 << (4 - $i);
            }
        }

        $bits = (($data << 10) | $remainder) ^ 0x5412;

        $last = $this->size - 1;

        // Порядок ячеек задан стандартом и идёт от СТАРШЕГО бита к младшему.
        // Записывать наоборот нельзя: сканер прочитает чужой номер маски и
        // снимет не тот узор — код перестанет читаться целиком, хотя данные
        // в нём разложены верно.
        $first = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];

        // Вторая копия — у двух других углов, на случай повреждения первой.
        $second = [
            [$last, 8], [$last - 1, 8], [$last - 2, 8], [$last - 3, 8],
            [$last - 4, 8], [$last - 5, 8], [$last - 6, 8],
            [8, $last - 7], [8, $last - 6], [8, $last - 5], [8, $last - 4],
            [8, $last - 3], [8, $last - 2], [8, $last - 1], [8, $last],
        ];

        for ($i = 0; $i < 15; $i++) {
            $bit = ($bits >> (14 - $i)) & 1;

            [$row, $col] = $first[$i];
            $this->matrix[$row][$col] = $bit;

            [$row, $col] = $second[$i];
            $this->matrix[$row][$col] = $bit;
        }
    }

    /** Сумма штрафов по четырём правилам стандарта. */
    private function penalty(): int
    {
        return $this->penaltyRuns()
            + $this->penaltyBlocks()
            + $this->penaltyFinderLike()
            + $this->penaltyBalance();
    }

    /** Правило 1: подряд идущие модули одного цвета длиннее четырёх. */
    private function penaltyRuns(): int
    {
        $score = 0;

        foreach ([true, false] as $byRow) {
            for ($a = 0; $a < $this->size; $a++) {
                $run = 1;

                for ($b = 1; $b < $this->size; $b++) {
                    $current = $byRow ? $this->matrix[$a][$b] : $this->matrix[$b][$a];
                    $previous = $byRow ? $this->matrix[$a][$b - 1] : $this->matrix[$b - 1][$a];

                    if ($current === $previous) {
                        $run++;
                        continue;
                    }

                    if ($run >= 5) {
                        $score += 3 + ($run - 5);
                    }

                    $run = 1;
                }

                if ($run >= 5) {
                    $score += 3 + ($run - 5);
                }
            }
        }

        return $score;
    }

    /** Правило 2: одноцветные квадраты 2×2. */
    private function penaltyBlocks(): int
    {
        $score = 0;

        for ($row = 0; $row < $this->size - 1; $row++) {
            for ($col = 0; $col < $this->size - 1; $col++) {
                $value = $this->matrix[$row][$col];

                if ($value === $this->matrix[$row][$col + 1]
                    && $value === $this->matrix[$row + 1][$col]
                    && $value === $this->matrix[$row + 1][$col + 1]) {
                    $score += 3;
                }
            }
        }

        return $score;
    }

    /** Правило 3: узоры, похожие на поисковые, — сканер принял бы их за угол. */
    private function penaltyFinderLike(): int
    {
        $score = 0;
        $patterns = [
            [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1],
        ];

        foreach ([true, false] as $byRow) {
            for ($a = 0; $a < $this->size; $a++) {
                for ($b = 0; $b <= $this->size - 11; $b++) {
                    foreach ($patterns as $pattern) {
                        $match = true;

                        for ($k = 0; $k < 11; $k++) {
                            $value = $byRow
                                ? $this->matrix[$a][$b + $k]
                                : $this->matrix[$b + $k][$a];

                            if ($value !== $pattern[$k]) {
                                $match = false;
                                break;
                            }
                        }

                        if ($match) {
                            $score += 40;
                        }
                    }
                }
            }
        }

        return $score;
    }

    /** Правило 4: перекос доли тёмных модулей от половины. */
    private function penaltyBalance(): int
    {
        $dark = 0;

        for ($row = 0; $row < $this->size; $row++) {
            $dark += array_sum($this->matrix[$row]);
        }

        $percent = $dark * 100 / ($this->size * $this->size);

        return (int) (abs(intdiv((int) $percent - 50, 5)) * 10);
    }

    private function set(int $row, int $col, int $value, bool $reserved = false): void
    {
        if ($row < 0 || $row >= $this->size || $col < 0 || $col >= $this->size) {
            return;
        }

        $this->matrix[$row][$col] = $value;

        if ($reserved) {
            $this->reserved[$row][$col] = true;
        }
    }
}
