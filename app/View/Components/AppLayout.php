<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * 🧱 AppLayout
 *
 * Компонент общего макета приложения.
 * Используется как обёртка для страниц: <x-app-layout>
 *
 * Возвращает шаблон `resources/views/layouts/app.blade.php`
 */
class AppLayout extends Component
{
    /**
     * 🧩 render()
     *
     * Возвращает представление, связанное с этим компонентом.
     * Обычно содержит: <html>, <head>, <body> и `{{ $slot }}`
     *
     * @return \Illuminate\View\View
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
