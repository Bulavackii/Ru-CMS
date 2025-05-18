<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 👤 ProfileController
 *
 * Контроллер редактирования основных данных пользователя:
 * имя и email.
 */
class ProfileController extends Controller
{
    /**
     * ✏️ edit()
     *
     * 📄 Показывает форму редактирования профиля:
     * - имя
     * - email
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $user = Auth::user();

        return view('frontend.profile.edit', compact('user'));
    }

    /**
     * 💾 update()
     *
     * Обновляет основные данные профиля:
     * - имя
     * - email
     *
     * 📌 Валидация:
     * - имя обязательно, до 255 символов
     * - email валидный и до 255 символов
     *
     * ❗ Уникальность email не проверяется — можно добавить при необходимости
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            // При необходимости: 'email' => 'required|email|unique:users,email,' . $user->id
        ]);

        // 💾 Обновление данных
        $user->name  = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        // ✅ Возврат с успешным сообщением
        return redirect()
            ->route('dashboard')
            ->with('success', '✅ Данные профиля обновлены.');
    }
}
