<?php

namespace Modules\Install\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    /**
     * 🚀 Стартовая страница установщика
     */
    public function welcome()
    {
        return view('Install::welcome');
    }

    /**
     * 🔍 Проверка системных требований
     */
    public function requirements()
    {
        $requirements = [
            'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO' => extension_loaded('pdo'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'Writable: storage/' => is_writable(storage_path()),
        ];

        return view('Install::requirements', compact('requirements'));
    }

    /**
     * ⚙️ Конфигурация базы данных и генерация .env
     */
    public function database(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('Install::database', [
                'csrf' => csrf_token(),
                'session' => session()->all()
            ]);
        }

        if ($request->isMethod('post')) {
            $envPath = base_path('.env');

            // 🛠 Создаём .env, если не существует
            if (!File::exists($envPath)) {
                File::copy(base_path('.env.example'), $envPath);
            }

            // 🔧 Обновляем переменные
            $updates = [
                'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
                'DB_CONNECTION' => $request->input('connection', 'mysql'),
                'DB_HOST' => $request->input('host', '127.0.0.1'),
                'DB_PORT' => $request->input('port', '3306'),
                'DB_DATABASE' => $request->input('database'),
                'DB_USERNAME' => $request->input('username'),
                'DB_PASSWORD' => $request->input('password'),
                'SESSION_DRIVER' => 'database',
            ];

            $env = File::get($envPath);

            foreach ($updates as $key => $value) {
                $pattern = "/^{$key}=.*$/m";
                $replacement = "{$key}=\"{$value}\"";

                if (Str::contains($env, "{$key}=")) {
                    $env = preg_replace($pattern, $replacement, $env);
                } else {
                    $env .= "\n{$replacement}";
                }
            }

            File::put($envPath, $env);

            // ♻️ Очистка кэша и генерация ключа
            Artisan::call('config:clear');
            Artisan::call('key:generate --force');

            return redirect()->route('install.admin');
        }

        return view('Install::database');
    }

    /**
     * 👤 Создание администратора и запуск миграций
     */
    public function admin(Request $request)
    {
        if ($request->isMethod('post')) {
            // 🧱 Генерация таблицы сессий и запуск миграций
            Artisan::call('session:table');
            Artisan::call('migrate --force');

            // 👤 Создание админа
            DB::table('users')->insert([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'is_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('install.finish');
        }

        return view('Install::admin');
    }

    /**
     * 🏁 Завершение установки
     */
    public function finish()
    {
        File::put(storage_path('install.lock'), 'Installed');
        return view('Install::finish');
    }
}
