<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use App\Services\MonitoringService;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Игнорируем несущественные ошибки
            if ($this->isIgnorable($e)) {
                return false;
            }

            // Использование MonitoringService для отслеживания ошибок
            try {
                if (app()->bound('monitoring')) {
                    $monitoring = app('monitoring');
                    $monitoring->trackError($e, [
                        'request_data' => request()->except(['password', 'password_confirmation']),
                    ]);
                } else {
                    // Fallback на старое логирование
                    Log::channel('daily')->error($e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } catch (\Exception $monitoringError) {
                // Fallback на старое логирование
                Log::channel('daily')->error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        // Глобальный обработчик для необработанных исключений
        set_exception_handler(function ($e) {
            Log::channel('critical')->critical('Uncaught exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Возвращаем пользователю красивую ошибку
            if (!app()->environment('local')) {
                return response()->view('errors.500', [], 500);
            }
        });
    }

    /**
     * Определяет, можно ли игнорировать ошибку
     */
    protected function isIgnorable(Throwable $e): bool
    {
        $ignorableMessages = [
            'Trying to access array offset on value of type null',
            'Undefined array key',
            'Division by zero',
        ];

        foreach ($ignorableMessages as $message) {
            if (str_contains($e->getMessage(), $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Отправка оповещения о необработанной ошибке.
     *
     * Здесь был file_get_contents на api.telegram.org БЕЗ таймаута. Если
     * сервис недоступен или заблокирован, каждая необработанная ошибка
     * подвешивала запрос до default_socket_timeout — то есть до минуты.
     * Внешний сервис получал возможность положить сайт, и починка одной
     * ошибки превращалась в отказ всего сайта.
     *
     * Теперь это общий канал оповещений с таймаутом: адрес задаёт владелец,
     * а без адреса не отправляется ничего.
     */
    protected function sendToTelegram(Throwable $e): void
    {
        send_alert('error', $e->getMessage(), [
            'file' => $e->getFile() . ':' . $e->getLine(),
        ]);
    }

    /**
     * Отправка ошибки по email
     */
    protected function sendToEmail(Throwable $e): void
    {
        try {
            \Illuminate\Support\Facades\Mail::send(
                'emails.error',
                ['exception' => $e],
                function ($message) {
                    $message->to(config('mail.from.address'))
                            ->subject('CMS Error - ' . now()->format('Y-m-d H:i:s'));
                }
            );
        } catch (Throwable $mailError) {
            Log::channel('daily')->error('Failed to send email notification', [
                'error' => $mailError->getMessage(),
            ]);
        }
    }

    /**
     * Рендер ошибки для API
     */
    public function render($request, Throwable $e)
    {
        // Для API возвращаем JSON
        if ($request->is('api/*') || $request->wantsJson()) {
            $isProduction = app()->environment('production');
            
            // В production скрываем детали ошибки
            if ($isProduction) {
                // Для известных HTTP исключений показываем сообщение
                if ($this->isHttpException($e)) {
                    $statusCode = $e->getStatusCode();
                    $message = match($statusCode) {
                        404 => 'Ресурс не найден',
                        403 => 'Доступ запрещен',
                        401 => 'Требуется аутентификация',
                        422 => 'Ошибка валидации данных',
                        500 => 'Внутренняя ошибка сервера',
                        default => 'Произошла ошибка',
                    };
                    
                    return response()->json([
                        'error' => [
                            'message' => $message,
                            'code' => $statusCode,
                        ],
                    ], $statusCode);
                }
                
                // Для остальных ошибок показываем общее сообщение
                return response()->json([
                    'error' => [
                        'message' => 'Произошла ошибка. Обратитесь к администратору.',
                        'code' => $this->getStatusCode($e),
                    ],
                ], $this->getStatusCode($e));
            }
            
            // В development показываем все детали
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'details' => $e->getTrace(),
                ],
            ], $this->getStatusCode($e));
        }

        // Для веба возвращаем view
        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }

        return parent::render($request, $e);
    }

    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        if ($e instanceof \Illuminate\Database\QueryException) {
            return 500;
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        return 500;
    }
}
