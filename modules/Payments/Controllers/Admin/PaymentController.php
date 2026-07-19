<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use Illuminate\Support\Arr;
use Modules\Payments\Models\PaymentMethod;

class PaymentController extends Controller
{
    /**
     * ?? Список всех способов оплаты
     */
    public function index()
    {
        $methods = PaymentMethod::orderByDesc('id')->get();

        return view('Payments::admin.index', compact('methods'));
    }

    /**
     * ? Форма создания нового способа оплаты
     */
    public function create()
    {
        return view('Payments::admin.create');
    }

    /**
     * ?? Сохранение нового способа оплаты
     */
    public function store(PaymentMethodRequest $request)
    {
        // ?? Создание записи
        $validated = $this->normalizeSettings($request->validated());

        PaymentMethod::create($validated);

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Способ оплаты добавлен');
    }

    /**
     * ?? Форма редактирования метода оплаты
     */
    public function edit($id)
    {
        $method = PaymentMethod::findOrFail($id);

        return view('Payments::admin.edit', compact('method'));
    }

    /**
     * ?? Обновление способа оплаты
     */
    public function update(PaymentMethodRequest $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $validated = $this->normalizeSettings($request->validated(), $method);

        $method->update($validated);

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Способ оплаты обновлён');
    }

    /**
     * ?? Удаление метода оплаты
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Способ оплаты удалён');
    }

    private function normalizeSettings(array $validated, ?PaymentMethod $method = null): array
    {
        $settingsKeys = PaymentMethod::SETTINGS_FIELDS;
        $settings = Arr::only($validated, $settingsKeys);
        $settings = array_map(static function ($value) {
            if (is_string($value)) {
                $value = trim($value);
                return $value == '' ? null : $value;
            }

            return $value;
        }, $settings);

        $settingsFromInput = $validated['settings'] ?? [];
        $validated = Arr::except($validated, array_merge($settingsKeys, ['settings']));

        $hasSettings = (bool) array_filter($settings, static fn ($value) => $value !== null);
        if ($method !== null || $hasSettings || !empty($settingsFromInput)) {
            $baseSettings = $method?->settings ?? [];
            if (!is_array($baseSettings)) {
                $baseSettings = [];
            }
            if (!is_array($settingsFromInput)) {
                $settingsFromInput = [];
            }
            $validated['settings'] = array_merge($baseSettings, $settingsFromInput, $settings);
        }

        return $validated;
    }
}
