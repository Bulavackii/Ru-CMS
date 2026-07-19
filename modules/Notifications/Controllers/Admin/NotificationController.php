<?php

namespace Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Events\NotificationCreated;
use App\Events\NotificationUpdated;
use App\Events\NotificationDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Notifications\Models\Notification;

class NotificationController extends Controller
{
    /**
     * 📋 Отображение списка уведомлений
     */
    public function index(Request $request)
    {
        $query = Notification::query();

        // Поиск
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Фильтр по типу
        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        // Фильтр по целевой аудитории
        if ($request->filled('target')) {
            $query->where('target', $request->input('target'));
        }

        // Фильтр по позиции
        if ($request->filled('position')) {
            $query->byPosition($request->input('position'));
        }

        // Фильтр по статусу
        if ($request->filled('enabled')) {
            if ($request->input('enabled') === '1') {
                $query->enabled();
            } elseif ($request->input('enabled') === '0') {
                $query->where('enabled', false);
            }
        }

        // Сортировка
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['id', 'title', 'type', 'target', 'position', 'created_at', 'updated_at', 'priority', 'views_count'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('created_at');
        }

        $notifications = $query->paginate(10)->withQueryString();

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
    public function update(NotificationRequest $request, Notification $notification)
    {
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        // 💾 Обновление в базе
        $notification->update($validated);

        // Очистка кэша
        Cache::forget('notifications_active');

        // Событие
        NotificationUpdated::dispatch($notification);

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление обновлено!');
    }

    /**
     * 🗑️ Удаление уведомления
     */
    public function destroy(Notification $notification)
    {
        NotificationDeleted::dispatch($notification);
        $notification->delete();

        // Очистка кэша
        Cache::forget('notifications_active');

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление удалено!');
    }

    /**
     * 🔁 Переключение включённости уведомления
     */
    public function toggle(Notification $notification)
    {
        $notification->enabled = !$notification->enabled;
        $notification->updated_by = auth()->id();
        $notification->save();

        // Очистка кэша
        Cache::forget('notifications_active');

        // Событие
        NotificationUpdated::dispatch($notification);

        return redirect()->back()->with('success', 'Статус уведомления обновлён.');
    }

    /**
     * 📦 Массовые действия
     */
    public function bulkAction(Request $request)
    {
        $ids = $request->input('selected', []);

        if (empty($ids)) {
            return back()->with('error', 'Выберите уведомления для действия.');
        }

        if ($request->action === 'delete') {
            $notifications = Notification::whereIn('id', $ids)->get();
            foreach ($notifications as $notification) {
                NotificationDeleted::dispatch($notification);
            }
            Notification::whereIn('id', $ids)->delete();
            Cache::forget('notifications_active');
            return back()->with('success', 'Выбранные уведомления удалены.');
        }

        if ($request->action === 'enable') {
            Notification::whereIn('id', $ids)->update([
                'enabled' => true,
                'updated_by' => auth()->id()
            ]);
            Cache::forget('notifications_active');
            return back()->with('success', 'Выбранные уведомления включены.');
        }

        if ($request->action === 'disable') {
            Notification::whereIn('id', $ids)->update([
                'enabled' => false,
                'updated_by' => auth()->id()
            ]);
            Cache::forget('notifications_active');
            return back()->with('success', 'Выбранные уведомления отключены.');
        }

        return back()->with('error', 'Выберите действие.');
    }

    /**
     * 👁️ Предпросмотр уведомления
     */
    public function preview(Notification $notification)
    {
        return view('Notifications::admin.preview', compact('notification'));
    }
}
