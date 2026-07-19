<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * 👤 ProfileController
 *
 * Контроллер управления профилем пользователя:
 * 🔹 Просмотр и редактирование профиля
 * 🔹 Удаление учётной записи
 */
class ProfileController extends Controller
{
    /**
     * ✏️ edit()
     *
     * Показывает форму редактирования профиля пользователя
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * 💾 update()
     *
     * Обновляет информацию профиля пользователя:
     * - 🔐 Использует кастомный `ProfileUpdateRequest` с валидацией
     * - 🔁 Обнуляет поле `email_verified_at`, если email был изменён
     *
     * @param  \App\Http\Requests\ProfileUpdateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        // 📤 Если email изменился — сбрасываем подтверждение
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }

    /**
     * ❌ destroy()
     *
     * Удаляет учётную запись пользователя:
     * - 🔐 Проверяет текущий пароль
     * - 🚪 Выполняет logout
     * - 🧹 Очищает сессию и токен
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // ✅ Подтверждение удаления аккаунта с проверкой пароля
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // ⛔ Выход из аккаунта
        Auth::logout();

        // 🗑️ Удаление пользователя (мягкое или полное — зависит от модели)
        $user->delete();

        // 🔁 Очистка и перегенерация сессии
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
