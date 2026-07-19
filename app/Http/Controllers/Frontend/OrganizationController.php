<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 🏢 OrganizationController
 *
 * Контроллер для редактирования организационных данных
 * юридических лиц в личном кабинете.
 */
class OrganizationController extends Controller
{
    /**
     * ✏️ edit()
     *
     * Отображает форму редактирования данных компании.
     *
     * 🔐 Доступно только для пользователей с флагом `is_company = true`.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit()
    {
        $user = Auth::user();

        // 🔒 Проверка: только для юридических лиц
        if (!$user->is_company) {
            return redirect()
                ->route('dashboard')
                ->with('error', '❌ Раздел доступен только юридическим лицам.');
        }

        return view('frontend.dashboard.organization', compact('user'));
    }

    /**
     * 💾 update()
     *
     * Обновляет организационные данные пользователя:
     * - Название компании
     * - ИНН
     * - ОГРН
     *
     * 🔐 Доступно только для юридических лиц.
     * 🛡️ Валидация обязательных полей.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // 🔒 Повторная проверка — защита от прямого запроса
        if (!$user->is_company) {
            return redirect()
                ->route('dashboard')
                ->with('error', '❌ Раздел доступен только юридическим лицам.');
        }

        // 🛡️ Валидация входных данных
        $request->validate([
            'company_name' => 'required|string|max:255',
            'inn'          => 'required|string|max:20',
            'ogrn'         => 'required|string|max:20',
        ]);

        // 💾 Обновление данных
        $user->company_name = $request->input('company_name');
        $user->inn          = $request->input('inn');
        $user->ogrn         = $request->input('ogrn');
        $user->save();

        // ✅ Уведомление об успешном обновлении
        return redirect()
            ->route('dashboard')
            ->with('success', '✅ Организационные данные обновлены.');
    }
}
