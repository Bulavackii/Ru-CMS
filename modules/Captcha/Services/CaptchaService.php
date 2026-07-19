<?php

namespace Modules\Captcha\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Captcha\Services\YandexCaptchaService;

/**
 * 🔒 Сервис каптчи
 *
 * Поддерживает несколько типов:
 * - image: Классическая картинка с кодом
 * - slider: Слайдер (перетаскивание)
 * - math: Математическое выражение
 * - question: Вопрос-ответ
 * - recaptcha: Google reCAPTCHA (если настроен)
 */
class CaptchaService
{
    /**
     * Генерация каптчи
     */
    public function generate(string $type = 'image', array $options = [])
    {
        $method = 'generate' . ucfirst($type);

        if (!method_exists($this, $method)) {
            $type = 'image'; // fallback
            $method = 'generateImage';
        }

        return $this->$method($options);
    }

    /**
     * Генерация изображения с кодом
     */
    protected function generateImage(array $options = [])
    {
        $length = $options['length'] ?? 5;
        $width = $options['width'] ?? 200;
        $height = $options['height'] ?? 60;

        $code = $this->generateCode($length);
        $this->storeCode($code);

        // Создаем изображение
        $image = imagecreate($width, $height);

        // Цвета
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
        $lineColor = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));

        // Добавляем шум
        for ($i = 0; $i < 10; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $lineColor);
        }

        // Добавляем линии
        for ($i = 0; $i < 5; $i++) {
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        // Текст
        $font = __DIR__ . '/font.ttf'; // Можно добавить шрифт
        if (file_exists($font)) {
            imagettftext($image, 20, rand(-10, 10), 10, 40, $textColor, $font, $code);
        } else {
            imagestring($image, 5, 10, 20, $code, $textColor);
        }

        // Сохраняем в буфер
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return [
            'type' => 'image',
            'html' => '<img src="data:image/png;base64,' . base64_encode($imageData) . '" alt="CAPTCHA" style="border: 1px solid #ccc;">',
            'code' => $code, // для тестирования
        ];
    }

    /**
     * Генерация слайдера
     */
    protected function generateSlider(array $options = [])
    {
        $position = rand(30, 170); // Позиция слайдера
        $this->storeCode($position, 'slider');

        return [
            'type' => 'slider',
            'html' => '
                <div style="position: relative; width: 200px; height: 50px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 25px;">
                    <div style="position: absolute; left: ' . $position . 'px; top: 2px; width: 46px; height: 46px; background: #4CAF50; border-radius: 50%; cursor: grab;" class="captcha-slider-handle"></div>
                    <div style="position: absolute; left: 10px; top: 15px; color: #999; font-size: 12px;">Перетащите →</div>
                </div>
                <input type="hidden" name="captcha_position" value="' . $position . '">
            ',
            'position' => $position, // для тестирования
        ];
    }

    /**
     * Генерация математической задачи
     */
    protected function generateMath(array $options = [])
    {
        $a = rand(10, 50);
        $b = rand(1, 20);
        $operations = ['+', '-', '*'];
        $op = $operations[array_rand($operations)];

        switch ($op) {
            case '+':
                $answer = $a + $b;
                break;
            case '-':
                $answer = $a - $b;
                break;
            case '*':
                $answer = $a * $b;
                break;
            default:
                $answer = $a + $b;
        }

        $this->storeCode($answer, 'math');

        return [
            'type' => 'math',
            'html' => '
                <div style="padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
                    <strong>Решите задачу:</strong><br>
                    <span style="font-size: 18px; font-weight: bold;">' . $a . ' ' . $op . ' ' . $b . ' = ?</span>
                </div>
                <input type="hidden" name="captcha_math_answer" value="' . $answer . '">
            ',
            'answer' => $answer, // для тестирования
        ];
    }

    /**
     * Генерация вопроса
     */
    protected function generateQuestion(array $options = [])
    {
        $questions = [
            'Сколько будет 2+2?' => '4',
            'Какой цвет неба днем?' => 'голубой',
            'Сколько месяцев в году?' => '12',
            'Столица России?' => 'москва',
        ];

        $keys = array_keys($questions);
        $question = $keys[array_rand($keys)];
        $answer = $questions[$question];

        $this->storeCode($answer, 'question');

        return [
            'type' => 'question',
            'html' => '
                <div style="padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">
                    <strong>Вопрос:</strong><br>
                    <span style="font-size: 16px;">' . $question . '</span>
                </div>
            ',
            'answer' => $answer, // для тестирования
        ];
    }

    /**
     * Проверка каптчи
     */
    public function verify(string $userInput, string $type = 'image'): bool
    {
        $stored = Session::get('captcha_code');
        $storedType = Session::get('captcha_type');

        if (!$stored || $storedType !== $type) {
            return false;
        }

        // Для слайдера проверяем диапазон
        if ($type === 'slider') {
            $position = (int)$stored;
            $userPosition = (int)$userInput;
            return abs($position - $userPosition) < 10; // Допуск 10px
        }

        // Для математики и вопросов - точное совпадение
        if ($type === 'math' || $type === 'question') {
            return (string)$stored === (string)$userInput;
        }

        // Для изображения - регистронезависимое сравнение
        return strtolower(trim($userInput)) === strtolower(trim($stored));
    }

    /**
     * Генерация HTML для вставки в любое место
     */
    public function render(string $type = 'image', array $options = [])
    {
        // Яндекс.Капча
        if ($type === 'yandex') {
            $config = config('captcha.yandex', []);
            if (!empty($config['client_key']) && !empty($config['server_key'])) {
                $yandexService = new YandexCaptchaService($config);
                $html = '<div class="captcha-wrapper" data-captcha-type="yandex">';
                $html .= $yandexService->render($options);
                $html .= '<input type="hidden" name="captcha_token" id="captcha-token">';
                $html .= '</div>';
                return $html;
            }
            // Fallback на обычную капчу если Яндекс не настроена
            $type = 'image';
        }

        $captcha = $this->generate($type, $options);

        $html = '<div class="captcha-wrapper" data-captcha-type="' . $type . '">';
        $html .= $captcha['html'];

        // Поле ввода
        if ($type === 'image' || $type === 'math' || $type === 'question') {
            $html .= '<input type="text" name="captcha" required placeholder="Введите код"
                       class="captcha-input" style="margin-top: 10px; padding: 5px; width: 200px; border: 1px solid #ccc; border-radius: 3px;">';
        } elseif ($type === 'slider') {
            $html .= '<input type="hidden" name="captcha" value="slider">';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Генерация JavaScript для динамической загрузки
     */
    public function renderJS(string $selector, string $type = 'image', array $options = [])
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
                }

                loadCaptcha();

                // Обновление по клику
                container.addEventListener('click', function(e) {
                    if (e.target.tagName === 'IMG' || e.target.classList.contains('captcha-refresh')) {
                        loadCaptcha();
                    }
                });
            })();
            </script>
        ";
    }

    /**
     * Генерация кода
     */
    protected function generateCode(int $length): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * Сохранение кода в сессию
     */
    protected function storeCode($code, $type = 'image')
    {
        Session::put('captcha_code', $code);
        Session::put('captcha_type', $type);
        Session::put('captcha_time', time());
    }

    /**
     * Очистка старой каптчи
     */
    public function cleanup()
    {
        $time = Session::get('captcha_time');
        if ($time && (time() - $time) > 600) { // 10 минут
            Session::forget(['captcha_code', 'captcha_type', 'captcha_time']);
            return true;
        }
        return false;
    }
}
