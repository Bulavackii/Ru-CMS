<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Установка RU CMS')</title>
    <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ local_css('font-awesome/all.min.css') }}">
    @stack('styles')
</head>
<body class="bg-gray-50">
    @yield('content')
    @stack('scripts')
</body>
</html>

