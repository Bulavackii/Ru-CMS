<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

/**
 * 🛠️ Контроллер отчётов об ошибках
 *
 * Позволяет:
 * 🔹 Отправить отчёт об ошибке с вложением (через email)
 * 🔹 Посмотреть геолокацию по IP
 * 🔹 Получить системную информацию
 */
class ErrorReportController extends Controller
{
    /**
     * 🖊️ Метод form()
     *
     * 📄 Отображает форму отправки отчёта об ошибке
     *
     * @return \Illuminate\View\View
     */
    public function form()
    {
        return view('admin.error.report-error');
    }

    /**
     * 📧 Метод send()
     *
     * 📨 Обрабатывает отправку отчёта об ошибке через форму
     *
     * 🔍 Валидация:
     *   - message: обязательно, минимум 10 символов
     *   - email: опционально, должен быть email
     *   - file: опциональный файл, не более 2 МБ
     *
     * 📎 Поддерживает вложение файла
     * 📬 Отправляет email с шаблоном и вложением (если есть)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(Request $request)
    {
        // ✅ Проверка данных
        $request->validate([
            'message' => 'required|string|min:10',
            'email' => 'nullable|email',
            'file' => 'nullable|file|max:2048',
        ]);

        // 📦 Сбор информации
        $data = [
            'message' => $request->input('message'),
            'email' => $request->input('email'),
            'user' => $request->user(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->headers->get('referer'),
        ];

        // 📁 Обработка прикреплённого файла (если есть)
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('error-attachments', 'public');
            $data['file_path'] = Storage::disk('public')->path($path);
        }

        // ✉️ Отправка письма с данными
        Mail::raw(
            view('admin.error.mail', $data)->render(),
            function ($message) use ($data) {
                $message->to(config('mail.from.address'), 'Support')
                        ->subject('🛠️ Ошибка на сайте')
                        ->replyTo($data['email'] ?? config('mail.from.address'));

                // 📎 Добавляем вложение, если есть
                if (!empty($data['file_path'])) {
                    $message->attach($data['file_path']);
                }
            }
        );

        // ✅ Уведомление об успешной отправке
        return back()->with('success', '✅ Ваше сообщение отправлено. Спасибо!');
    }

    /**
     * 🌍 Метод geolocation()
     *
     * 🔎 Получает данные геолокации по IP пользователя
     *
     * Использует пакет:
     *   - 📦 stevebauman/location
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function geolocation(Request $request)
    {
        $ip = $request->ip(); // 🌐 IP пользователя
        $location = Location::get($ip); // 📍 Получение местоположения

        return view('admin.error.geolocation', [
            'ip' => $ip,
            'location' => $location,
            'userAgent' => $request->userAgent(),
            'language' => $request->server('HTTP_ACCEPT_LANGUAGE'),
            'timestamp' => now(), // 🕒 Время запроса
        ]);
    }

    /**
     * 💻 Метод systemInfo()
     *
     * 🧾 Показывает техническую информацию о сервере
     *
     * Представление может включать:
     *   - PHP-версию
     *   - Версию Laravel
     *   - Инфо о базе данных и окружении
     *
     * @return \Illuminate\View\View
     */
    public function systemInfo()
    {
        return view('admin.error.system-info');
    }
}
