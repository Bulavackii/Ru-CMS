<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * 🙍 GuestLayout
 *
 * Макет для гостевых страниц (неавторизованных пользователей).
 * Пример: страницы входа, регистрации, сброса пароля и т.п.
 */
class GuestLayout extends Component
{
    /**
     * 📄 render()
     *
     * Возвращает Blade-шаблон `resources/views/layouts/guest.blade.php`,
     * в который будет вставляться `{{ $slot }}` содержимого.
     *
     * @return \Illuminate\View\View
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
