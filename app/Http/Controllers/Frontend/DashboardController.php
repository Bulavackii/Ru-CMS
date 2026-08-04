<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Payments\Models\Order;

/**
 * 🧑‍💼 DashboardController
 *
 * Контроллер личного кабинета пользователя на клиентской части.
 *
 * Функции:
 * 🔹 Просмотр и редактирование профиля
 * 🔹 Просмотр последних заказов
 */
class DashboardController extends Controller
{
    /**
     * 👤 index()
     *
     * 📄 Отображает главную страницу личного кабинета пользователя
     *
     * Загружает:
     * - текущего пользователя
     * - последние 5 заказов без подгрузки items (для экономии)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // 📦 Последние 5 заказов (без items)
        $orders = Order::with(['paymentMethod', 'deliveryMethod']) // Методы оплаты и доставки
            ->select('orders.*')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('frontend.dashboard.index', compact('user', 'orders'));
    }

    /**
     * ✏️ edit()
     *
     * 📄 Форма редактирования профиля пользователя
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $user = Auth::user();
        return view('frontend.dashboard.edit', compact('user'));
    }

    /**
     * 💾 update()
     *
     * ✅ Обновляет данные профиля пользователя
     *
     * 🔍 Валидирует поля: ФИО, адреса, контакты, а также юр.информацию
     * 💼 Поддерживает оба варианта: физлицо и юрлицо (`is_company`)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 🛡️ Валидация данных профиля
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:50',
            // Адреса страниц в сетях. Проверяем как адрес: строка без
            // проверки пропускала бы что угодно, а ссылка потом вела бы в
            // никуда прямо из профиля.
            'vk'                => 'nullable|url|max:255',
            'max'               => 'nullable|url|max:255',
            'zip'               => 'nullable|string|max:20',
            'is_company'        => 'nullable|boolean',
            'company_name'      => 'nullable|string|max:255',
            'inn'               => 'nullable|string|max:20',
            'ogrn'              => 'nullable|string|max:20',
            'ceo'               => 'nullable|string|max:255',
            'address_legal'     => 'nullable|string|max:255',
            'address_actual'    => 'nullable|string|max:255',
            'okato'             => 'nullable|string|max:20',
        ]);

        // 💾 Обновление данных пользователя
        $user->fill([
            'name'              => $validated['name'],
            'address'           => $validated['address'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'vk'                => $validated['vk'] ?? null,
            'max'               => $validated['max'] ?? null,
            'zip'               => $validated['zip'] ?? null,
            'is_company'        => $request->has('is_company'), // Флаг: юрлицо или нет
            'company_name'      => $validated['company_name'] ?? null,
            'inn'               => $validated['inn'] ?? null,
            'ogrn'              => $validated['ogrn'] ?? null,
            'ceo'               => $validated['ceo'] ?? null,
            'address_legal'     => $validated['address_legal'] ?? null,
            'address_actual'    => $validated['address_actual'] ?? null,
            'okato'             => $validated['okato'] ?? null,
        ]);

        $user->save();

        // ✅ Возврат с уведомлением
        return redirect()->route('dashboard')->with('success', '✅ Профиль успешно обновлён');
    }
}
