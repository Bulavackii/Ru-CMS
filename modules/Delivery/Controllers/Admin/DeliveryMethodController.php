<?php

namespace Modules\Delivery\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryMethodRequest;
use Modules\Delivery\Models\DeliveryMethod;

class DeliveryMethodController extends Controller
{
    /**
     * 📦 Отображение списка всех методов доставки.
     */
    public function index()
    {
        $methods = DeliveryMethod::orderBy('sort_order')->orderBy('title')->get();

        return view('Delivery::admin.index', compact('methods'));
    }

    /**
     * ➕ Показ формы создания нового метода доставки.
     */
    public function create()
    {
        return view('Delivery::admin.create');
    }

    /**
     * 💾 Обработка отправки формы создания.
     */
    public function store(DeliveryMethodRequest $request)
    {
        // 📥 Создание нового метода доставки
        $validated = $request->validated();

        // Обработка API настроек из JSON
        if ($request->has('api_settings_json') && !empty($request->api_settings_json)) {
            $apiSettings = json_decode($request->api_settings_json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $validated['api_settings'] = $apiSettings;
            }
        }

        // Обработка регионов (уже обработано в Request, но убедимся)
        if (isset($validated['regions']) && empty($validated['regions'])) {
            $validated['regions'] = null;
        }

        DeliveryMethod::create($validated);

        // 🔙 Возвращаем пользователя к списку с флеш-сообщением
        return redirect()->route('admin.delivery.index')
                         ->with('success', 'Метод доставки добавлен');
    }

    /**
     * ✏️ Показ формы редактирования существующего метода доставки.
     */
    public function edit(DeliveryMethod $delivery)
    {
        return view('Delivery::admin.edit', compact('delivery'));
    }

    /**
     * ♻️ Обновление существующего метода доставки.
     */
    public function update(DeliveryMethodRequest $request, DeliveryMethod $delivery)
    {
        // 🔄 Обновляем поля в существующей записи
        $validated = $request->validated();

        // Обработка API настроек из JSON
        if ($request->has('api_settings_json') && !empty($request->api_settings_json)) {
            $apiSettings = json_decode($request->api_settings_json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $validated['api_settings'] = $apiSettings;
            }
        }

        // Обработка регионов (уже обработано в Request, но убедимся)
        if (isset($validated['regions']) && empty($validated['regions'])) {
            $validated['regions'] = null;
        }

        $delivery->update($validated);

        // 🔙 Перенаправление со статусом
        return redirect()->route('admin.delivery.index')
                         ->with('success', 'Метод доставки обновлён');
    }

    /**
     * 🗑️ Удаление метода доставки.
     */
    public function destroy(DeliveryMethod $delivery)
    {
        // ❌ Удаляем запись
        $delivery->delete();

        // 🔙 Назад со всплывающим уведомлением
        return back()->with('success', 'Метод доставки удалён');
    }
}
