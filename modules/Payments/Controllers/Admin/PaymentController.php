<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Payments\Models\PaymentMethod;

class PaymentController extends Controller
{
    /**
     * 📋 Список всех способов оплаты
     */
    public function index()
    {
        $methods = PaymentMethod::orderByDesc('id')->get();

        return view('Payments::admin.index', compact('methods'));
    }

    /**
     * ➕ Форма создания нового способа оплаты
     */
    public function create()
    {
        return view('Payments::admin.create');
    }

    /**
     * 💾 Сохранение нового способа оплаты
     */
    public function store(Request $request)
    {
        // ✅ Валидация
        $request->validate([
            'title'       => 'required|string|max:255',            // Название метода
            'description' => 'nullable|string',                    // Описание
            'type'        => 'required|in:offline,online',         // Тип: офлайн или онлайн
            'active'      => 'boolean',                            // Активен или нет
        ]);

        // 📦 Создание записи
        PaymentMethod::create([
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'active'      => $request->boolean('active'),
            'settings'    => [], // 🔧 Настройки по умолчанию (для онлайн-методов)
        ]);

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Способ оплаты добавлен');
    }

    /**
     * ✏️ Форма редактирования метода оплаты
     */
    public function edit($id)
    {
        $method = PaymentMethod::findOrFail($id);

        return view('Payments::admin.edit', compact('method'));
    }

    /**
     * 🔄 Обновление способа оплаты
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:offline,online',
            'active'      => 'boolean',
        ]);

        $method = PaymentMethod::findOrFail($id);

        $method->update([
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'active'      => $request->boolean('active'),
        ]);

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Способ оплаты обновлён');
    }

    /**
     * 🗑 Удаление метода оплаты
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Способ оплаты удалён');
    }
}
