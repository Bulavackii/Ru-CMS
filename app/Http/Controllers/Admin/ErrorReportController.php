<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Stevebauman\Location\Facades\Location;

/**
 * Служебные страницы панели: отчёт об ошибке, геолокация и сводка о системе.
 */
class ErrorReportController extends Controller
{
    /**
     * Разрешённые вложения к отчёту.
     *
     * Раньше правило было просто `file|max:2048` — прикрепить можно было что
     * угодно, включая .php. Файл уходил на публичный диск (см. store() ниже),
     * то есть становился доступен по прямой ссылке.
     */
    private const ATTACHMENT_MIMES = 'jpg,jpeg,png,gif,webp,txt,log,pdf';

    public function form()
    {
        return view('admin.error.report-error', [
            'mailReady' => $this->mailIsConfigured(),
        ]);
    }

    /**
     * Отправляет отчёт об ошибке на почту, указанную в настройках сайта.
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:10|max:5000',
            'email' => 'nullable|email',
            'file' => 'nullable|file|max:2048|mimes:' . self::ATTACHMENT_MIMES,
        ]);

        // Без настроенной почты Mail бросает исключение, и страница отдавала
        // 500 вместо внятного ответа. Проверяем заранее.
        if (! $this->mailIsConfigured()) {
            return back()
                ->withInput()
                ->withErrors(['message' => __('admin.system.er_mail_off')]);
        }

        // Ключ намеренно НЕ 'message': Mail::send() кладёт в данные вьюхи
        // собственный $message (объект письма) и затёр бы текст обращения.
        $data = [
            'body' => $request->input('message'),
            'email' => $request->input('email'),
            'user' => $request->user(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->headers->get('referer'),
        ];

        // Вложение кладём на ЗАКРЫТЫЙ диск: скриншот или лог может содержать
        // что угодно, а public отдаётся по прямой ссылке без авторизации.
        $storedPath = null;

        if ($request->hasFile('file')) {
            $storedPath = $request->file('file')->store('error-attachments', 'local');
            $data['file_path'] = Storage::disk('local')->path($storedPath);
        }

        try {
            // Шаблон письма — HTML. Прежний Mail::raw() рендерил его и слал
            // как простой текст, то есть получатель видел разметку. Форма
            // send(['html' => …]) отдаёт вьюху телом письма и, в отличие от
            // Mail::html(), входит в контракт мейлера — значит, её
            // перехватывает Mail::fake() в тестах.
            Mail::send(['html' => 'admin.error.mail'], $data, function ($message) use ($data) {
                $message->to(config('mail.from.address'), config('mail.from.name') ?: 'Support')
                    ->subject(__('admin.system.er_title') . ' — ' . config('app.name'));

                if (! empty($data['email'])) {
                    $message->replyTo($data['email']);
                }

                if (! empty($data['file_path'])) {
                    $message->attach($data['file_path']);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить отчёт об ошибке: ' . $e->getMessage());

            // Письмо не ушло — вложение на диске больше не нужно.
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            return back()
                ->withInput()
                ->withErrors(['message' => __('admin.errors.mail_failed', ['error' => $e->getMessage()])]);
        }

        return back()->with('success', __('admin.flash.report_sent'));
    }

    /**
     * Показывает, как сайт видит текущего посетителя.
     */
    public function geolocation(Request $request)
    {
        $ip = $request->ip();

        // Для локального адреса внешний сервис звать бессмысленно: географии
        // у 127.0.0.1 нет, а запрос всё равно уйдёт и будет ждать таймаута.
        $isLocal = in_array($ip, ['127.0.0.1', '::1'], true)
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

        $location = null;

        if (! $isLocal) {
            try {
                $location = Location::get($ip) ?: null;
            } catch (\Throwable $e) {
                Log::warning('Геолокация недоступна: ' . $e->getMessage());
            }
        }

        return view('admin.error.geolocation', [
            'ip' => $ip,
            'isLocal' => $isLocal,
            'location' => $location,
            'userAgent' => $request->userAgent(),
            'language' => $request->server('HTTP_ACCEPT_LANGUAGE'),
            'timestamp' => now(),
        ]);
    }

    /**
     * Сводка об окружении — то, что просят приложить к обращению в поддержку.
     */
    public function systemInfo()
    {
        return view('admin.error.system-info', [
            'groups' => $this->systemGroups(),
            'extensions' => $this->loadedExtensions(),
            'debug' => (bool) config('app.debug'),
            'storageLinked' => is_link(public_path('storage')) || is_dir(public_path('storage')),
        ]);
    }

    /**
     * Сводка сгруппирована по смыслу: платформа, база, сервер, приложение.
     * Раньше это была плоская сетка из десяти карточек без порядка.
     */
    private function systemGroups(): array
    {
        return [
            'g_runtime' => [
                'f_laravel' => app()->version(),
                'f_php' => PHP_VERSION,
                'f_sapi' => PHP_SAPI,
                'f_env' => app()->environment(),
            ],
            'g_db' => [
                'f_db_driver' => (string) config('database.default'),
                'f_db_version' => $this->databaseVersion(),
                'f_db_name' => (string) config('database.connections.' . config('database.default') . '.database'),
            ],
            'g_server' => [
                'f_os' => PHP_OS . ' ' . php_uname('r'),
                'f_memory' => (string) ini_get('memory_limit'),
                'f_upload' => (string) ini_get('upload_max_filesize'),
                'f_post' => (string) ini_get('post_max_size'),
                'f_exec' => ini_get('max_execution_time') . ' ' . __('admin.system.f_seconds'),
                'f_disk' => $this->freeDiskSpace(),
            ],
            'g_app' => [
                'f_version' => (string) config('app.version', '1.0.0'),
                'f_url' => (string) config('app.url'),
                'f_locale' => app()->getLocale(),
                'f_tz' => (string) config('app.timezone'),
                'f_time' => now()->format('d.m.Y H:i:s'),
                'f_cache' => (string) config('cache.default'),
                'f_queue' => (string) config('queue.default'),
                'f_session' => (string) config('session.driver'),
                'f_mail' => (string) config('mail.default'),
                'f_path' => base_path(),
            ],
        ];
    }

    private function databaseVersion(): string
    {
        try {
            $row = DB::selectOne('select version() as version');

            return (string) ($row->version ?? __('admin.system.unknown'));
        } catch (\Throwable $e) {
            // На SQLite такого запроса нет, а страница не должна из-за этого падать.
            return __('admin.system.unknown');
        }
    }

    private function freeDiskSpace(): string
    {
        $free = @disk_free_space(base_path());

        if ($free === false) {
            return __('admin.system.unknown');
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $free > 0 ? (int) floor(log($free, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($free / (1024 ** $power), 1) . ' ' . $units[$power];
    }

    private function loadedExtensions(): array
    {
        $extensions = get_loaded_extensions();
        sort($extensions, SORT_NATURAL | SORT_FLAG_CASE);

        return $extensions;
    }

    /**
     * Почта считается настроенной, если задан отправитель и драйвер не «пустой».
     */
    private function mailIsConfigured(): bool
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');

        if ($from === '' || in_array($mailer, ['array', 'null'], true)) {
            return false;
        }

        // Для SMTP без хоста отправка гарантированно упадёт.
        if ($mailer === 'smtp' && ! config('mail.mailers.smtp.host')) {
            return false;
        }

        return true;
    }
}
