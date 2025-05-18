<?php

namespace Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Notifications\Models\Notification;

class NotificationController extends Controller
{
    /**
     * 📋 Отображение списка уведомлений
     */
    public function index()
    {
        // Загружаем уведомления по убыванию даты
        $notifications = Notification::latest()->paginate(10);
        return view('Notifications::admin.index', compact('notifications'));
    }

    /**
     * ➕ Форма создания нового уведомления
     */
    public function create()
    {
        return view('Notifications::admin.create');
    }

    /**
     * 💾 Сохранение нового уведомления
     */
    public function store(Request $request)
    {
        // 🛡️ Валидация входящих данных
        $validated = $request->validate([
            'title'        => 'required|string|max:255',        // 📌 Заголовок
            'message'      => 'required|string',                // 💬 Содержимое
            'type'         => 'required|in:text,cookie',        // 📋 Тип: обычное или cookie
            'target'       => 'required|in:all,admin,user',     // 👥 Целевая аудитория
            'position'     => 'required|in:top,bottom,fullscreen', // 📍 Расположение
            'duration'     => 'nullable|integer|min:0',         // ⏱️ Время показа
            'icon'         => 'nullable|string|max:100',        // 🖼️ Иконка
            'route_filter' => 'nullable|string|max:255',        // 🗺️ URL-фильтр
            'cookie_key'   => 'nullable|string|max:255',        // 🍪 Ключ для cookie
            'bg_color'     => 'nullable|string|max:20',         // 🎨 Цвет фона
            'text_color'   => 'nullable|string|max:20',         // 🎨 Цвет текста
        ]);

        // 🚦 Включаем уведомление по умолчанию
        $validated['enabled'] = true;

        // 💽 Создание записи в БД
        Notification::create($validated);

        // 🔁 Редирект с сообщением
        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление создано!');
    }

    /**
     * ✏️ Форма редактирования уведомления
     */
    public function edit(Notification $notification)
    {
        return view('Notifications::admin.edit', compact('notification'));
    }

    /**
     * 🛠️ Обновление существующего уведомления
     */
    public function update(Request $request, Notification $notification)
    {
        // 🔄 Повторная валидация перед сохранением
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'type'         => 'required|in:text,cookie',
            'target'       => 'required|in:all,admin,user',
            'position'     => 'required|in:top,bottom,fullscreen',
            'duration'     => 'nullable|integer|min:0',
            'icon'         => 'nullable|string|max:100',
            'route_filter' => 'nullable|string|max:255',
            'cookie_key'   => 'nullable|string|max:255',
            'bg_color'     => 'nullable|string|max:20',
            'text_color'   => 'nullable|string|max:20',
        ]);

        // 💾 Обновление в базе
        $notification->update($validated);

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление обновлено!');
    }

    /**
     * 🗑️ Удаление уведомления
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление удалено!');
    }

    /**
     * 🔁 Переключение включённости уведомления
     */
    public function toggle(Notification $notification)
    {
        $notification->enabled = !$notification->enabled;
        $notification->save();

        return redirect()->back()->with('success', 'Статус уведомления обновлён.');
    }
}
