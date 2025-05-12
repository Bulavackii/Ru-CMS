<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Метод для отображения формы создания пользователя
    public function create()
    {
        return view('users::admin.create'); // Убедитесь, что у вас есть этот Blade-шаблон
    }

    public function store(Request $request)
    {
        // Валидация данных
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Получаем значение флага is_admin из формы (по умолчанию будет 0, если галочка не установлена)
        $isAdmin = $request->has('is_admin') ? 1 : 0;

        // Создаем нового пользователя
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_admin' => $isAdmin, // Сохраняем значение is_admin
        ]);

        // Перенаправление с сообщением об успешном создании
        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно создан!');
    }


    // Метод для отображения списка пользователей
    public function index(Request $request)
    {
        // Получаем роль из параметров запроса
        $currentRole = $request->get('role');

        // Получаем запрос для поиска
        $search = $request->get('search', '');

        // Получаем пользователей, фильтруя по роли и поисковому запросу
        $users = User::when($currentRole, function ($query) use ($currentRole) {
            return $query->where('is_admin', $currentRole === 'admin' ? 1 : 0);
        })
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(10);

        return view('users::admin.index', compact('users', 'currentRole', 'search'));
    }


    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.users.index')->with('error', 'Пользователь не найден');
        }

        if ($user->is_admin) {
            return redirect()->route('admin.users.index')->with('error', 'Невозможно удалить администратора');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Пользователь удалён');
    }

    public function toggleRole($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.users.index')->with('error', 'Пользователь не найден');
        }

        // Меняем роль пользователя
        $user->is_admin = !$user->is_admin;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Роль пользователя изменена');
    }
}
