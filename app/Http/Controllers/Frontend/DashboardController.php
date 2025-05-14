<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Payments\Models\Order;

class DashboardController extends Controller
{
    /**
     * 👤 Отображение личного кабинета
     */
    public function index()
    {
        $user = Auth::user();

        $orders = Order::with('paymentMethod', 'items')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('frontend.dashboard.index', compact('user', 'orders'));
    }

    /**
     * ✏️ Форма редактирования профиля
     */
    public function edit()
    {
        $user = Auth::user();
        return view('frontend.dashboard.edit', compact('user'));
    }

    /**
     * 💾 Сохранение данных профиля
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:50',
            'telegram'          => 'nullable|string|max:50',
            'whatsapp'          => 'nullable|string|max:50',
            'vk'                => 'nullable|string|max:255',
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

        $user->fill([
            'name'              => $validated['name'],
            'address'           => $validated['address'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'telegram'          => $validated['telegram'] ?? null,
            'whatsapp'          => $validated['whatsapp'] ?? null,
            'vk'                => $validated['vk'] ?? null,
            'zip'               => $validated['zip'] ?? null,
            'is_company'        => $request->has('is_company'),
            'company_name'      => $validated['company_name'] ?? null,
            'inn'               => $validated['inn'] ?? null,
            'ogrn'              => $validated['ogrn'] ?? null,
            'ceo'               => $validated['ceo'] ?? null,
            'address_legal'     => $validated['address_legal'] ?? null,
            'address_actual'    => $validated['address_actual'] ?? null,
            'okato'             => $validated['okato'] ?? null,
        ]);

        $user->save();

        return redirect()->route('dashboard')->with('success', 'Профиль успешно обновлён');
    }
}
