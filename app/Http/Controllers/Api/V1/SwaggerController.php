<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="RU CMS API",
 *     version="1.0.0",
 *     description="API документация для RU CMS - модульной CMS для России и СНГ",
 *     @OA\Contact(
 *         email="support@rucms.ru"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Введите токен в формате: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Аутентификация и авторизация"
 * )
 *
 * @OA\Tag(
 *     name="News",
 *     description="Управление новостями"
 * )
 *
 * @OA\Tag(
 *     name="Categories",
 *     description="Управление категориями"
 * )
 *
 * @OA\Tag(
 *     name="Pages",
 *     description="Управление страницами"
 * )
 */
class SwaggerController extends Controller
{
    public function index()
    {
        return view('swagger.index');
    }
}

