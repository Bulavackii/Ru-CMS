<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомление об истечении лицензии</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header h1 {
            color: #dc2626;
            margin: 0;
            font-size: 24px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid;
        }
        .alert-critical {
            background-color: #fef2f2;
            border-color: #dc2626;
            color: #991b1b;
        }
        .alert-warning {
            background-color: #fffbeb;
            border-color: #f59e0b;
            color: #92400e;
        }
        .alert-info {
            background-color: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
        }
        .info-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        .info-value {
            color: #111827;
            font-weight: 500;
        }
        .days-left {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .days-left.critical {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .days-left.warning {
            background-color: #fef3c7;
            color: #d97706;
        }
        .days-left.info {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Уведомление об истечении лицензии</h1>
        </div>

        <p>Здравствуйте, <strong>{{ $admin->name }}</strong>!</p>

        <div class="alert alert-{{ $daysLeft <= 7 ? 'critical' : ($daysLeft <= 14 ? 'warning' : 'info') }}">
            <strong>Внимание!</strong> Лицензия вашей CMS скоро истечет.
        </div>

        <div class="days-left {{ $daysLeft <= 7 ? 'critical' : ($daysLeft <= 14 ? 'warning' : 'info') }}">
            @if($daysLeft <= 0)
                Лицензия истекла!
            @else
                Осталось: {{ $daysLeft }} {{ $daysLeft === 1 ? 'день' : ($daysLeft < 5 ? 'дня' : 'дней') }}
            @endif
        </div>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Тарифный план:</span>
                <span class="info-value">{{ ucfirst($plan) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Дата истечения:</span>
                <span class="info-value">{{ $expiresAt->format('d.m.Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Лицензионный ключ:</span>
                <span class="info-value" style="font-family: monospace; font-size: 12px;">{{ $licenseKey }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Уровень срочности:</span>
                <span class="info-value">{{ ucfirst($urgency) }}</span>
            </div>
        </div>

        @if($daysLeft <= 7)
            <div class="alert alert-critical">
                <strong>Срочно!</strong> Лицензия истекает менее чем через неделю. 
                Рекомендуем продлить лицензию как можно скорее, чтобы избежать ограничений в работе системы.
            </div>
        @elseif($daysLeft <= 14)
            <div class="alert alert-warning">
                <strong>Важно!</strong> Лицензия истекает через две недели. 
                Пожалуйста, продлите лицензию заранее.
            </div>
        @else
            <div class="alert alert-info">
                Это напоминание о приближающемся сроке истечения лицензии. 
                Вы получите дополнительные уведомления за 14, 7, 3 и 1 день до истечения.
            </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/admin/subscriptions" class="button">
                Продлить лицензию →
            </a>
        </div>

        <div class="footer">
            <p>Это автоматическое уведомление от системы управления контентом.</p>
            <p>Если у вас возникли вопросы, свяжитесь с поддержкой.</p>
        </div>
    </div>
</body>
</html>

