<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка сервера</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 1rem;
        }
        .error-code {
            font-size: 4rem;
            font-weight: bold;
            color: #ef4444;
            margin: 0;
            line-height: 1;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1rem 0 0.5rem;
            color: #1f2937;
        }
        .error-message {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #1f2937;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .support-info {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Что-то пошло не так</h2>
        <p class="error-message">
            Мы уже работаем над устранением проблемы. Пожалуйста, попробуйте обновить страницу или вернитесь позже.
        </p>

        <div class="actions">
            <a href="/" class="btn btn-primary">На главную</a>
            <button onclick="window.location.reload()" class="btn btn-secondary">Обновить страницу</button>
        </div>

        <div class="support-info">
            Если проблема повторяется, обратитесь в поддержку<br>
            Технический код: <strong>{{ uniqid() }}</strong>
        </div>
    </div>

    <script>
        // Автоматическое обновление через 30 секунд
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
