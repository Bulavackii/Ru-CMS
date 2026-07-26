<?php

namespace Modules\Captcha\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\Captcha\Services\YandexCaptchaService;

/**
 * 🔒 Сервис каптчи.
 *
 * Типы: image (картинка с кодом), slider (перетащить ползунок),
 * math (пример), question (вопрос-ответ), yandex (SmartCaptcha).
 *
 * ── Что было переделано 26.07.2026 и почему ────────────────────────────────
 *
 * 1. ОТВЕТ ЛЕЖАЛ В РАЗМЕТКЕ. generateMath() выводил
 *    <input type="hidden" name="captcha_math_answer" value="42">, а
 *    generateSlider() — <input type="hidden" name="captcha_position" value="…">.
 *    То есть правильный ответ отдавался клиенту прямо в HTML: достаточно было
 *    открыть исходный код страницы. Каптча не защищала ни от чего.
 *
 * 2. СЛАЙДЕР НЕ ПРОХОДИЛСЯ В ПРИНЦИПЕ. render() подставлял
 *    <input type="hidden" name="captcha" value="slider">, а verify() делает
 *    (int) от присланного значения: (int)'slider' === 0, и сравнение
 *    abs($position - 0) < 10 при позиции 30..170 не выполнялось никогда.
 *    Плюс перетаскивать было нечего — обработчика мыши не существовало.
 *
 * 3. КОД ХРАНИЛСЯ В ОДНОЙ ЯЧЕЙКЕ СЕССИИ. Вторая каптча на странице затирала
 *    код первой, и проверка первой формы проваливалась всегда. Теперь у
 *    каждого экземпляра свой идентификатор: session('captcha.instances')[id].
 *
 * Совместимость: verify($input, $type) без идентификатора работает как раньше
 * (берётся самый свежий экземпляр нужного типа) — на этой сигнатуре держатся
 * правило валидации 'captcha:image' и модуль Комментариев.
 */
class CaptchaService
{
    /** Ключ в сессии, под которым живут все выданные экземпляры. */
    private const STORE = 'captcha.instances';

    /** Сколько живёт выданный код, секунд. */
    private const TTL = 600;

    /** Сколько экземпляров держим в сессии, чтобы она не пухла. */
    private const MAX_INSTANCES = 12;

    /**
     * Генерация каптчи.
     *
     * @return array{type:string,id:string,html:string}
     */
    public function generate(string $type = 'image', array $options = []): array
    {
        $method = 'generate' . ucfirst($type);

        if (!method_exists($this, $method)) {
            $type = 'image';
            $method = 'generateImage';
        }

        return $this->$method($options);
    }

    /**
     * Картинка с кодом.
     *
     * Опции: length (3–10), width, height, noise (0–3), lines (0–10).
     */
    protected function generateImage(array $options = []): array
    {
        $length = $this->clamp($options['length'] ?? 5, 3, 10);
        $width  = $this->clamp($options['width'] ?? 200, 80, 600);
        $height = $this->clamp($options['height'] ?? 60, 30, 200);
        $noise  = $this->clamp($options['noise'] ?? 1, 0, 3);
        $lines  = $this->clamp($options['lines'] ?? 5, 0, 10);

        $code = $this->generateCode($length);
        $id = $this->storeCode($code, 'image');

        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 255, 255, 255));

        $lineColor = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));

        // Шум: 40 точек на единицу уровня — уровень 0 означает «без шума»
        for ($i = 0; $i < $noise * 40; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $lineColor);
        }

        for ($i = 0; $i < $lines; $i++) {
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        // Символы рисуются по одному, со сдвигом по вертикали: слитная строка
        // одним imagestring() распознаётся автоматикой заметно легче
        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $step = max($charWidth + 2, (int) (($width - 20) / max(1, $length)));
        $x = (int) max(6, ($width - $step * $length) / 2);

        for ($i = 0; $i < strlen($code); $i++) {
            $textColor = imagecolorallocate($image, rand(0, 90), rand(0, 90), rand(0, 90));
            $y = (int) (($height - $charHeight) / 2) + rand(-6, 6);
            imagestring($image, $font, $x + $i * $step, max(2, $y), $code[$i], $textColor);
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return [
            'type' => 'image',
            'id'   => $id,
            'html' => '<img src="data:image/png;base64,' . base64_encode($imageData) . '"'
                . ' alt="Проверочный код" class="captcha-image"'
                . ' style="border:1px solid #ccc;max-width:100%;height:auto;display:block">',
        ];
    }

    /**
     * Слайдер: дотащить ползунок до цели.
     *
     * Опции: width, height, tolerance (допуск попадания в пикселях).
     *
     * Правильная позиция в разметку НЕ попадает — только на сервер. Клиент
     * присылает то, куда реально дотащил ползунок.
     */
    protected function generateSlider(array $options = []): array
    {
        $width     = $this->clamp($options['width'] ?? 260, 160, 600);
        $height    = $this->clamp($options['height'] ?? 44, 32, 80);
        $tolerance = $this->clamp($options['tolerance'] ?? 10, 4, 40);

        $handle = $height - 6;
        $maxPosition = $width - $handle - 6;
        $target = rand((int) ($maxPosition * 0.45), $maxPosition);

        $id = $this->storeCode($target, 'slider', ['tolerance' => $tolerance]);

        // Метка цели рисуется ВНУТРИ картинки-дорожки, а не CSS-свойством
        // left:Npx. Иначе правильный ответ читался бы прямо из разметки:
        // достаточно распарсить стиль и отправить это число.
        $track = $this->sliderTrack($width, $height, $target, $handle);

        $html = <<<HTML
<div class="captcha-slider" data-captcha-slider
     style="position:relative;width:{$width}px;max-width:100%;height:{$height}px;
            background-image:url('{$track}');background-size:100% 100%;
            border:1px solid #cbd5e1;user-select:none;touch-action:none">
    <div class="captcha-slider__handle" role="slider" tabindex="0"
         aria-label="Перетащите ползунок до отмеченного места"
         style="position:absolute;left:3px;top:3px;width:{$handle}px;height:{$handle}px;background:#4f46e5;cursor:grab"></div>
</div>
HTML;

        return ['type' => 'slider', 'id' => $id, 'html' => $html];
    }

    /**
     * Дорожка слайдера картинкой: фон, подпись и метка цели.
     *
     * Цель существует только в пикселях. В разметке её координаты нет —
     * значит, чтобы «попасть», её придётся именно разглядеть.
     */
    private function sliderTrack(int $width, int $height, int $target, int $handle): string
    {
        $image = imagecreatetruecolor($width, $height);

        $bg     = imagecolorallocate($image, 241, 245, 249);
        $mark   = imagecolorallocate($image, 209, 250, 229);
        $edge   = imagecolorallocate($image, 16, 185, 129);
        $hint   = imagecolorallocate($image, 148, 163, 184);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        imagefilledrectangle($image, $target + 3, 0, $target + 3 + $handle, $height, $mark);
        imagefilledrectangle($image, $target + 3, 0, $target + 5, $height, $edge);

        $label = 'Dotashchite polzunok do metki';
        $font = 2;
        $labelWidth = imagefontwidth($font) * strlen($label);
        if ($labelWidth < $width - 20) {
            imagestring($image, $font, (int) (($width - $labelWidth) / 2),
                (int) (($height - imagefontheight($font)) / 2), $label, $hint);
        }

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * Арифметический пример.
     *
     * Опции: min, max, operations (список из +, -, *).
     */
    protected function generateMath(array $options = []): array
    {
        $min = $this->clamp($options['min'] ?? 1, 0, 999);
        $max = $this->clamp($options['max'] ?? 20, $min + 1, 1000);

        $allowed = array_values(array_intersect(
            (array) ($options['operations'] ?? ['+', '-', '*']),
            ['+', '-', '*']
        ));
        if (!$allowed) {
            $allowed = ['+'];
        }

        $op = $allowed[array_rand($allowed)];
        $a = rand($min, $max);
        $b = rand($min, $max);

        // Умножение больших чисел человеку в уме не сдать — ограничиваем
        if ($op === '*') {
            $a = rand(2, min(12, max(2, $max)));
            $b = rand(2, min(12, max(2, $max)));
        }

        // Вычитание не должно уходить в минус: это лишняя путаница, а не защита
        if ($op === '-' && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        $answer = match ($op) {
            '-' => $a - $b,
            '*' => $a * $b,
            default => $a + $b,
        };

        $id = $this->storeCode($answer, 'math');

        // Ответ уходит ТОЛЬКО в сессию: раньше он ехал в скрытом поле формы
        $html = '<div class="captcha-math" style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0">'
            . '<span style="font-size:12px;color:#64748b">Решите пример:</span><br>'
            . '<strong style="font-size:18px;letter-spacing:.04em">' . $a . ' ' . $op . ' ' . $b . ' = ?</strong>'
            . '</div>';

        return ['type' => 'math', 'id' => $id, 'html' => $html];
    }

    /**
     * Вопрос со свободным ответом.
     *
     * Опции: questions — список пар ['q' => вопрос, 'a' => ответ].
     * Если не задан, берётся набор из конфига, а тот — из значений по умолчанию.
     */
    protected function generateQuestion(array $options = []): array
    {
        $pairs = $options['questions'] ?? config('captcha.questions') ?? [];
        $pairs = array_values(array_filter(
            (array) $pairs,
            fn ($pair) => is_array($pair) && trim((string) ($pair['q'] ?? '')) !== '' && trim((string) ($pair['a'] ?? '')) !== ''
        ));

        if (!$pairs) {
            $pairs = self::defaultQuestions();
        }

        $pair = $pairs[array_rand($pairs)];
        $id = $this->storeCode(mb_strtolower(trim((string) $pair['a'])), 'question');

        $html = '<div class="captcha-question" style="padding:10px 12px;background:#fffbeb;border:1px solid #fcd34d">'
            . '<span style="font-size:12px;color:#92400e">Ответьте на вопрос:</span><br>'
            . '<strong style="font-size:15px">' . e($pair['q']) . '</strong>'
            . '</div>';

        return ['type' => 'question', 'id' => $id, 'html' => $html];
    }

    /** Набор вопросов «из коробки». */
    public static function defaultQuestions(): array
    {
        return [
            ['q' => 'Сколько будет два плюс два?', 'a' => '4'],
            ['q' => 'Какого цвета небо в ясный день?', 'a' => 'голубое'],
            ['q' => 'Сколько месяцев в году?', 'a' => '12'],
            ['q' => 'Столица России?', 'a' => 'москва'],
            ['q' => 'Сколько дней в неделе?', 'a' => '7'],
        ];
    }

    /**
     * Проверка ответа.
     *
     * @param string      $userInput Что прислал пользователь
     * @param string      $type      Ожидаемый тип (для совместимости со старым вызовом)
     * @param string|null $id        Идентификатор экземпляра. Если не передан —
     *                               берётся самый свежий живой экземпляр этого
     *                               типа, как работало до появления пресетов.
     */
    public function verify(string $userInput, string $type = 'image', ?string $id = null): bool
    {
        $instance = $id !== null
            ? $this->instance($id)
            : $this->latestInstanceOfType($type);

        if (!$instance) {
            return false;
        }

        // Тип обязан совпасть: иначе пресет «слайдер» проверялся бы как image
        // и не пропускал бы никого
        if ($instance['type'] !== $type) {
            return false;
        }

        $passed = $this->matches($userInput, $instance);

        // Одноразовость: угаданный код нельзя переиспользовать повторной
        // отправкой той же формы
        if ($passed) {
            $this->forget($instance['id']);
        }

        return $passed;
    }

    /**
     * Проверка по одному идентификатору, без знания типа заранее —
     * тип берётся из самого экземпляра. Используется правилом валидации
     * `captcha` без параметра и вставленными пресетами.
     */
    public function verifyInstance(string $userInput, string $id): bool
    {
        $instance = $this->instance($id);

        return $instance ? $this->verify($userInput, $instance['type'], $id) : false;
    }

    /** Тип выданного экземпляра или null, если его нет/он протух. */
    public function typeOf(string $id): ?string
    {
        return $this->instance($id)['type'] ?? null;
    }

    /**
     * Готовая разметка для вставки в форму: сама каптча, поле ответа и
     * скрытый идентификатор экземпляра.
     */
    public function render(string $type = 'image', array $options = []): string
    {
        if ($type === 'yandex') {
            $config = config('captcha.yandex', []);
            if (!empty($config['client_key']) && !empty($config['server_key'])) {
                return '<div class="captcha-wrapper" data-captcha-type="yandex">'
                    . (new YandexCaptchaService($config))->render($options)
                    . '<input type="hidden" name="captcha_token" id="captcha-token">'
                    . '</div>';
            }
            $type = 'image';
        }

        $captcha = $this->generate($type, $options);
        $inputId = 'captcha-' . $captcha['id'];

        $html = '<div class="captcha-wrapper" data-captcha-type="' . e($captcha['type']) . '"'
            . ' data-captcha-id="' . e($captcha['id']) . '" style="display:inline-block;max-width:100%">';
        $html .= $captcha['html'];

        // Идентификатор экземпляра: по нему сервер понимает, какой именно
        // код проверять. Без него две каптчи на странице неразличимы.
        $html .= '<input type="hidden" name="captcha_id" value="' . e($captcha['id']) . '">';

        if ($captcha['type'] === 'slider') {
            // Значение подставляет перетаскивание; пустое поле = не тащили
            $html .= '<input type="hidden" name="captcha" value="">';
        } else {
            $html .= '<input type="text" name="captcha" id="' . e($inputId) . '" required autocomplete="off"'
                . ' placeholder="Ваш ответ" class="captcha-input"'
                . ' style="margin-top:8px;padding:6px 8px;width:100%;max-width:220px;border:1px solid #cbd5e1">';
        }

        $html .= '</div>';

        if ($captcha['type'] === 'slider') {
            $html .= $this->sliderScript();
        }

        return $html;
    }

    /**
     * Скрипт перетаскивания. Раньше слайдер был просто кружком без единого
     * обработчика — тащить было нечем, и пройти его было нельзя.
     * Вешается один раз на страницу, работает и мышью, и пальцем, и с клавиатуры.
     */
    protected function sliderScript(): string
    {
        return <<<'HTML'
<script>
(function () {
    if (window.__captchaSliderReady) return;
    window.__captchaSliderReady = true;

    function bind(box) {
        if (box.dataset.captchaBound) return;
        box.dataset.captchaBound = '1';

        var handle = box.querySelector('.captcha-slider__handle');
        var field = box.closest('.captcha-wrapper').querySelector('input[name="captcha"]');
        if (!handle || !field) return;

        var max = box.clientWidth - handle.offsetWidth - 6;
        var dragging = false;

        function move(x) {
            var rect = box.getBoundingClientRect();
            var pos = Math.min(Math.max(x - rect.left - handle.offsetWidth / 2, 0), max);
            handle.style.left = (pos + 3) + 'px';
            field.value = Math.round(pos);
        }

        handle.addEventListener('pointerdown', function (e) {
            dragging = true;
            handle.setPointerCapture(e.pointerId);
            handle.style.cursor = 'grabbing';
        });
        handle.addEventListener('pointermove', function (e) {
            if (dragging) move(e.clientX);
        });
        handle.addEventListener('pointerup', function () {
            dragging = false;
            handle.style.cursor = 'grab';
        });

        // Клавиатура: каптча не должна быть недоступна без мыши
        handle.addEventListener('keydown', function (e) {
            var step = e.shiftKey ? 20 : 4;
            var current = parseInt(field.value || '0', 10);
            if (e.key === 'ArrowRight') current += step;
            else if (e.key === 'ArrowLeft') current -= step;
            else return;

            e.preventDefault();
            current = Math.min(Math.max(current, 0), max);
            handle.style.left = (current + 3) + 'px';
            field.value = current;
        });
    }

    function scan() {
        document.querySelectorAll('[data-captcha-slider]').forEach(bind);
    }

    document.addEventListener('DOMContentLoaded', scan);
    if (document.readyState !== 'loading') scan();
    window.captchaBindSliders = scan;
})();
</script>
HTML;
    }

    /** Динамическая подгрузка каптчи в контейнер. */
    public function renderJS(string $selector, string $type = 'image', array $options = []): string
    {
        $endpoint = route('api.captcha.generate', ['type' => $type]);

        return "
            <script>
            (function() {
                const container = document.querySelector('{$selector}');
                if (!container) return;

                async function loadCaptcha() {
                    const response = await fetch('{$endpoint}');
                    const data = await response.json();
                    container.innerHTML = data.html;
                    if (window.captchaBindSliders) window.captchaBindSliders();
                }

                loadCaptcha();

                container.addEventListener('click', function(e) {
                    if (e.target.tagName === 'IMG' || e.target.classList.contains('captcha-refresh')) {
                        loadCaptcha();
                    }
                });
            })();
            </script>
        ";
    }

    /** Удаление протухших экземпляров. Зовётся при каждой выдаче. */
    public function cleanup(): int
    {
        $instances = (array) Session::get(self::STORE, []);
        $alive = array_filter($instances, fn ($item) => $this->isAlive($item));

        Session::put(self::STORE, $alive);

        return count($instances) - count($alive);
    }

    // ── Внутреннее ────────────────────────────────────────────────────────

    private function matches(string $userInput, array $instance): bool
    {
        $stored = $instance['code'];

        if ($instance['type'] === 'slider') {
            if (trim($userInput) === '') {
                return false; // ползунок не трогали
            }

            $tolerance = (int) ($instance['meta']['tolerance'] ?? 10);

            return abs((int) $stored - (int) $userInput) <= $tolerance;
        }

        if ($instance['type'] === 'math') {
            return trim($userInput) !== '' && (string) $stored === trim($userInput);
        }

        // Вопрос и картинка — без учёта регистра и краевых пробелов
        return mb_strtolower(trim($userInput)) === mb_strtolower(trim((string) $stored));
    }

    private function instance(string $id): ?array
    {
        $item = Session::get(self::STORE . '.' . $id);

        return ($item && $this->isAlive($item)) ? $item + ['id' => $id] : null;
    }

    private function latestInstanceOfType(string $type): ?array
    {
        $alive = [];

        foreach ((array) Session::get(self::STORE, []) as $id => $item) {
            if ($this->isAlive($item) && ($item['type'] ?? null) === $type) {
                $alive[] = $item + ['id' => $id];
            }
        }

        if (!$alive) {
            return null;
        }

        usort($alive, fn ($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));

        return $alive[0];
    }

    private function isAlive(mixed $item): bool
    {
        return is_array($item)
            && isset($item['time'])
            && (time() - (int) $item['time']) <= self::TTL;
    }

    private function forget(string $id): void
    {
        Session::forget(self::STORE . '.' . $id);
    }

    protected function generateCode(int $length): string
    {
        // Без похожих друг на друга символов: 0/O, 1/I/l
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    /**
     * Сохранение выданного кода. Возвращает идентификатор экземпляра —
     * именно он попадёт в форму скрытым полем.
     */
    protected function storeCode($code, string $type = 'image', array $meta = []): string
    {
        $this->cleanup();

        $instances = (array) Session::get(self::STORE, []);

        // Держим сессию в разумных размерах: если экземпляров накопилось
        // больше лимита, выбрасываем самые старые
        if (count($instances) >= self::MAX_INSTANCES) {
            uasort($instances, fn ($a, $b) => ($a['time'] ?? 0) <=> ($b['time'] ?? 0));
            $instances = array_slice($instances, -(self::MAX_INSTANCES - 1), null, true);
        }

        $id = Str::lower(Str::random(16));
        $instances[$id] = [
            'code' => $code,
            'type' => $type,
            'time' => time(),
            'meta' => $meta,
        ];

        Session::put(self::STORE, $instances);

        return $id;
    }

    private function clamp(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }
}
