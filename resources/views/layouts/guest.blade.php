<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Авторизация - RuShop CMS')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white shadow rounded p-6 w-full max-w-md">
            @yield('content')
        </div>
    </div>
</body>
</html>
