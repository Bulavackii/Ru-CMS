<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 🧑‍💼 Контроллер управления настройками аккаунта администратора
 *
 * Отвечает за отображение страницы настроек аккаунта, где админ
 * может увидеть информацию о себе и, например, версию базы данных.
 */
class AccountSettingsController extends Controller
{
    /**
     * 🧭 Метод index()
     *
     * 📋 Загружает страницу настроек аккаунта администратора.
     *
     * 🔹 Получает текущего авторизованного пользователя
     * 🔹 Выполняет SQL-запрос для получения версии базы данных
     * 🔹 Передаёт данные в Blade-представление
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 👤 Получаем текущего авторизованного пользователя
        $user = Auth::user();

        // 🛢️ Версия базы данных (например, «PostgreSQL 18.4 on x86_64-…»)
        $dbVersion = DB::selectOne('select version() as version')->version ?? 'N/A';

        // 📄 Отображаем представление 'admin.account.settings' с переданными данными
        return view('admin.account.settings', [
            'user' => $user,
            'dbVersion' => $dbVersion,
        ]);
    }
}
