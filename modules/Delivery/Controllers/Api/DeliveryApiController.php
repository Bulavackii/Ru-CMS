<?php

namespace Modules\Delivery\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\Delivery\Services\DeliveryCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API контроллер для работы с доставкой
 */
class DeliveryApiController extends Controller
{
    public function __construct(
        private DeliveryCalculatorService $calculator
    ) {}

    /**
     * Рассчитать стоимость доставки
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'delivery_method_id' => 'required|exists:delivery_methods,id',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'order_total' => 'nullable|numeric|min:0',
        ]);

        $method = DeliveryMethod::findOrFail($request->delivery_method_id);

        if (!$method->active) {
            return response()->json([
                'error' => 'Метод доставки неактивен',
            ], 400);
        }

        $params = [
            'city' => $request->city,
            'region' => $request->region,
            'weight' => $request->weight ?? 1,
            'order_total' => $request->order_total ?? 0,
        ];

        $result = $this->calculator->calculate($method, $params);

        return response()->json($result);
    }

    /**
     * Получить список пунктов выдачи
     */
    public function pickupPoints(Request $request): JsonResponse
    {
        $request->validate([
            'delivery_method_id' => 'required|exists:delivery_methods,id',
            'city' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
        ]);

        $method = DeliveryMethod::findOrFail($request->delivery_method_id);

        if (!$method->active || $method->type !== 'pickup') {
            return response()->json([
                'error' => 'Метод доставки не поддерживает пункты выдачи',
            ], 400);
        }

        $points = $this->calculator->getPickupPoints(
            $method,
            $request->city,
            $request->region
        );

        return response()->json([
            'points' => $points,
        ]);
    }

    /**
     * Получить доступные методы доставки для региона
     */
    public function availableMethods(Request $request): JsonResponse
    {
        $request->validate([
            'region' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'order_total' => 'nullable|numeric|min:0',
        ]);

        $methods = DeliveryMethod::active()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $result = [];

        foreach ($methods as $method) {
            // Проверка доступности в регионе
            if ($request->region && !$method->isAvailableInRegion($request->region)) {
                continue;
            }

            // Проверка ограничения по весу
            if ($request->weight && !$method->isWeightAllowed($request->weight)) {
                continue;
            }

            // Расчет стоимости
            $params = [
                'region' => $request->region,
                'weight' => $request->weight ?? 1,
                'order_total' => $request->order_total ?? 0,
            ];

            $calculation = $this->calculator->calculate($method, $params);

            if (isset($calculation['error'])) {
                continue;
            }

            $result[] = [
                'id' => $method->id,
                'title' => $method->title,
                'description' => $method->description,
                'type' => $method->type,
                'price' => $calculation['price'],
                'days' => $calculation['days'] ?? $method->min_days ?? 0,
                'delivery_days' => $method->delivery_days,
                'is_russian' => $method->is_russian,
                'api_enabled' => $method->api_enabled,
                'code' => $method->code,
                'message' => $calculation['message'] ?? null,
            ];
        }

        return response()->json([
            'methods' => $result,
        ]);
    }
}





