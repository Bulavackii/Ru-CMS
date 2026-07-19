<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка в CMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #e0e0e0;
        }
        .header {
            background: #ef4444;
            color: white;
            padding: 15px;
            border-radius: 6px 6px 0 0;
            font-weight: bold;
            font-size: 18px;
        }
        .content {
            padding: 20px;
            line-height: 1.6;
        }
        .error-details {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 13px;
            word-break: break-all;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            margin: 15px 0;
            font-size: 14px;
        }
        .label {
            font-weight: bold;
            color: #374151;
        }
        .value {
            color: #1f2937;
            word-break: break-all;
        }
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .stack-trace {
            background: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            🚨 Ошибка в CMS - {{ config('app.name', 'RU CMS') }}
        </div>

        <div class="content">
            <p><strong>Время:</strong> {{ now()->toDateTimeString() }}</p>

            <div class="info-grid">
                <div class="label">Сообщение:</div>
                <div class="value">{{ $exception->getMessage() }}</div>

                <div class="label">Файл:</div>
                <div class="value">{{ $exception->getFile() }}:{{ $exception->getLine() }}</div>

                <div class="label">URL:</div>
                <div class="value">{{ request()->fullUrl() ?? 'N/A' }}</div>

                <div class="label">Метод:</div>
                <div class="value">{{ request()->method() }}</div>

                <div class="label">IP:</div>
                <div class="value">{{ request()->ip() }}</div>

                <div class="label">Пользователь:</div>
                <div class="value">
                    @auth
                        ID: {{ auth()->id() }} - {{ auth()->user()->name }}
                    @else
                        Гость
                    @endauth
                </div>
            </div>

            @if($exception->getPrevious())
                <div class="error-details">
                    <strong>Предыдущая ошибка:</strong><br>
                    {{ $exception->getPrevious()->getMessage() }}
                </div>
            @endif

            <div class="error-details">
                <strong>Тип исключения:</strong><br>
                {{ get_class($exception) }}
            </div>

            @if(app()->environment('local'))
                <div class="stack-trace">
                    <strong>Stack Trace:</strong><br>
                    {!! nl2br(e($exception->getTraceAsString())) !!}
                </div>
            @endif

            <div class="footer">
                Это автоматическое уведомление. Пожалуйста, не отвечайте на него.<br>
                <strong>Версия:</strong> {{ config('app.version', '1.0.0') }} |
                <strong>Окружение:</strong> {{ app()->environment() }}
            </div>
        </div>
    </div>
</body>
</html>
