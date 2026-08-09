<?php

namespace Tests\Unit\Support;

use App\Support\QrCode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🔳 Проверка своего генератора QR-кода.
 *
 * Смотреть на картинку глазами бесполезно: неверный код выглядит ровно так
 * же, как верный. Поэтому тест ЧИТАЕТ код обратно — собственным разбором,
 * написанным здесь по стандарту и намеренно не использующим внутренности
 * генератора. Именно так поймались обе настоящие поломки: перевёрнутый
 * порядок битов формата и пропущенные совмещающие узоры на версиях от 7,
 * из-за которых код не читался ни одним сканером.
 */
class QrCodeTest extends TestCase
{
    #[Test]
    public function свг_имеет_ожидаемый_размер_и_разбирается_обратно_в_матрицу(): void
    {
        $text = 'RU CMS';
        $matrix = QrCode::matrix($text);
        $size = count($matrix);
        $scale = 6;
        $margin = 4;

        $svg = QrCode::svg($text, $scale, $margin);
        $side = ($size + $margin * 2) * $scale;

        $this->assertStringContainsString('width="' . $side . '"', $svg);
        $this->assertStringContainsString('viewBox="0 0 ' . $side . ' ' . $side . '"', $svg);

        // Соседние тёмные модули склеиваются в один прямоугольник, поэтому
        // сверяем не количество тегов, а восстановленную из них сетку.
        $grid = array_fill(0, $size, array_fill(0, $size, 0));

        preg_match_all('~<rect x="(\d+)" y="(\d+)" width="(\d+)" height="(\d+)"/>~', $svg, $rects, PREG_SET_ORDER);

        foreach ($rects as $rect) {
            [, $x, $y, $width, $height] = array_map('intval', $rect);

            $this->assertSame($scale, $height, 'высота прямоугольника — ровно один модуль');

            $row = intdiv($y, $scale) - $margin;

            for ($i = 0; $i < intdiv($width, $scale); $i++) {
                $grid[$row][intdiv($x, $scale) - $margin + $i] = 1;
            }
        }

        $this->assertSame($matrix, $grid);
    }

    #[Test]
    public function дата_адрес_содержит_тот_же_свг(): void
    {
        $uri = QrCode::dataUri('RU CMS');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $this->assertSame(
            QrCode::svg('RU CMS'),
            base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')))
        );
    }

    /**
     * Главная проверка: код читается обратно.
     *
     * Длины подобраны так, чтобы задеть все версии с 1 по 10 — в том числе
     * рубеж версии 7, где появляются сведения о версии и совмещающие узоры
     * на полосах синхронизации, и версии 8–10 с двумя группами блоков.
     */
    #[Test]
    public function код_читается_обратно_на_всех_версиях(): void
    {
        $cases = [
            'A',
            'RU CMS',
            'otpauth://totp/RU%20CMS:admin%40example.com?secret=DWYG22KAMXDEXKV3&issuer=RU%20CMS&algorithm=SHA1&digits=6&period=30',
            'https://example.com/страница/с/кириллицей?x=1',
        ];

        // По одной длине на каждую версию: 14, 26, 42, 62, 84, 106, 122, 152, 180, 213.
        foreach ([14, 26, 42, 62, 84, 106, 122, 152, 180, 213] as $length) {
            $cases[] = str_repeat('X', $length - 1) . 'Z';
        }

        foreach ($cases as $text) {
            $matrix = QrCode::matrix($text);
            $version = (count($matrix) - 17) / 4;

            $this->assertSame(
                $text,
                $this->readBack($matrix),
                'не читается обратно на версии ' . $version . ' (' . strlen($text) . ' байт)'
            );
        }
    }

    #[Test]
    public function слишком_длинный_текст_отвергается_явной_ошибкой(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        QrCode::svg(str_repeat('A', 214));
    }

    // ──────────────────────────────────────────────────────────────────
    // Разбор кода: своя реализация по стандарту, независимая от генератора
    // ──────────────────────────────────────────────────────────────────

    /** @param array<int, array<int, int>> $matrix */
    private function readBack(array $matrix): string
    {
        $size = count($matrix);
        $version = (int) (($size - 17) / 4);
        $reserved = $this->serviceModules($size, $version);

        $mask = $this->readMask($matrix);
        $stream = '';

        foreach ($this->trail($size, $reserved) as [$row, $col]) {
            $bit = $matrix[$row][$col];

            if ($this->maskCondition($mask, $row, $col)) {
                $bit ^= 1;
            }

            $stream .= $bit;
        }

        $bits = $this->deinterleave($stream, $version);

        $this->assertSame('0100', substr($bits, 0, 4), 'ожидался байтовый режим');

        $countBits = $version >= 10 ? 16 : 8;
        $length = bindec(substr($bits, 4, $countBits));

        $text = '';

        for ($i = 0; $i < $length; $i++) {
            $text .= chr(bindec(substr($bits, 4 + $countBits + $i * 8, 8)));
        }

        return $text;
    }

    /**
     * Собрать поток данных обратно из чередующихся блоков.
     *
     * Начиная с версии 4 слова разложены не подряд, а по кругу: первое слово
     * каждого блока, потом второе каждого и так далее. Читать такой код
     * подряд бессмысленно — получится связный мусор, в котором даже длина
     * возьмётся из чужого блока.
     */
    private function deinterleave(string $stream, int $version): string
    {
        // [слов коррекции на блок, блоков группы 1, слов данных в блоке
        //  группы 1, блоков группы 2, слов данных в блоке группы 2]
        [$g1Blocks, $g1Words, $g2Blocks, $g2Words] = [
            1 => [1, 16, 0, 0],
            2 => [1, 28, 0, 0],
            3 => [1, 44, 0, 0],
            4 => [2, 32, 0, 0],
            5 => [2, 43, 0, 0],
            6 => [4, 27, 0, 0],
            7 => [4, 31, 0, 0],
            8 => [2, 38, 2, 39],
            9 => [3, 36, 2, 37],
            10 => [4, 43, 1, 44],
        ][$version];

        $sizes = array_merge(
            array_fill(0, $g1Blocks, $g1Words),
            array_fill(0, $g2Blocks, $g2Words)
        );

        $blocks = array_fill(0, count($sizes), '');
        $offset = 0;

        for ($i = 0; $i < max($sizes); $i++) {
            foreach ($sizes as $block => $words) {
                if ($i >= $words) {
                    continue;
                }

                $blocks[$block] .= substr($stream, $offset, 8);
                $offset += 8;
            }
        }

        return implode('', $blocks);
    }

    /** Номер маски вынимается из сведений о формате первой копии. */
    private function readMask(array $matrix): int
    {
        $cells = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];

        $bits = '';

        foreach ($cells as [$row, $col]) {
            $bits .= $matrix[$row][$col];
        }

        $value = bindec($bits) ^ 0x5412;

        $this->assertSame(0, ($value >> 13) & 0b11, 'ожидался уровень коррекции M');

        return ($value >> 10) & 0b111;
    }

    /**
     * Карта служебных модулей — по описанию стандарта, а не по коду класса.
     *
     * @return array<int, array<int, bool>>
     */
    private function serviceModules(int $size, int $version): array
    {
        $map = array_fill(0, $size, array_fill(0, $size, false));

        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                $corner = ($row <= 8 && $col <= 8)
                    || ($row <= 8 && $col >= $size - 8)
                    || ($row >= $size - 8 && $col <= 8);

                $map[$row][$col] = $corner || $row === 6 || $col === 6;
            }
        }

        $centers = [
            1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
            6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46],
            10 => [6, 28, 50],
        ][$version];

        if ($centers !== []) {
            $first = $centers[0];
            $last = $centers[count($centers) - 1];

            foreach ($centers as $row) {
                foreach ($centers as $col) {
                    if ([$row, $col] === [$first, $first]
                        || [$row, $col] === [$first, $last]
                        || [$row, $col] === [$last, $first]) {
                        continue;
                    }

                    for ($dr = -2; $dr <= 2; $dr++) {
                        for ($dc = -2; $dc <= 2; $dc++) {
                            $map[$row + $dr][$col + $dc] = true;
                        }
                    }
                }
            }
        }

        if ($version >= 7) {
            for ($i = 0; $i < 18; $i++) {
                $map[intdiv($i, 3)][$size - 11 + $i % 3] = true;
                $map[$size - 11 + $i % 3][intdiv($i, 3)] = true;
            }
        }

        return $map;
    }

    /**
     * Порядок обхода данных: змейкой снизу вверх парами столбцов.
     *
     * @return array<int, array{int, int}>
     */
    private function trail(int $size, array $reserved): array
    {
        $cells = [];
        $upward = true;

        for ($right = $size - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            for ($step = 0; $step < $size; $step++) {
                $row = $upward ? $size - 1 - $step : $step;

                foreach ([$right, $right - 1] as $col) {
                    if (! $reserved[$row][$col]) {
                        $cells[] = [$row, $col];
                    }
                }
            }

            $upward = ! $upward;
        }

        return $cells;
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
}
