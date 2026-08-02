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
        $methods = DeliveryMethod::orderBy('sort_order')->orderBy('title')->paginate(5);

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
                         ->with('success', __('admin.flash.delivery_added'));
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
                         ->with('success', __('admin.flash.delivery_updated'));
    }

    /**
     * 🗑️ Удаление метода доставки.
     */
    /**
     * Проверка связи со службой доставки.
     *
     * Сохранённые ключи, а не присланные формой: проверять надо ровно то,
     * чем служба будет пользоваться при реальном заказе.
     */
    public function check(DeliveryMethod $delivery)
    {
        $result = app(\Modules\Delivery\Services\ServiceChecker::class)->check($delivery);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function destroy(DeliveryMethod $delivery)
    {
        // ❌ Удаляем запись
        $delivery->delete();

        // 🔙 Назад со всплывающим уведомлением
        return back()->with('success', __('admin.flash.delivery_deleted'));
    }
}
