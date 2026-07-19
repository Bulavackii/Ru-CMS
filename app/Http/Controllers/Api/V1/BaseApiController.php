<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

abstract class BaseApiController extends Controller
{
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    protected function paginate($query, array $filters = [], int $perPage = 15): JsonResponse
    {
        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if (method_exists($query->getModel(), 'scope' . ucfirst($field))) {
                    $query = $query->$field($value);
                }
            }
        }

        $data = $query->paginate($perPage);

        return $this->success([
            'items' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }

    protected function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = validator($data, $rules, $messages);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    protected function resource($data, JsonResource $resource): JsonResponse
    {
        return $this->success($resource::make($data));
    }

    protected function resourceCollection($data, JsonResource $resource): JsonResponse
    {
        return $this->success($resource::collection($data));
    }
}
