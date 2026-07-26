<?php

namespace Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Events\NotificationCreated;
use App\Events\NotificationUpdated;
use App\Events\NotificationDeleted;
use Illuminate\Http\Request;
use Modules\Notifications\Models\Notification;

/**
 * 🔔 Уведомления-баннеры для посетителей сайта.
 *
 * NB: это НЕ центр уведомлений админки (колокольчик в шапке) — тот живёт в
 * App\Http\Controllers\Admin\NotificationController и работает с таблицей
 * admin_notifications. До 26.07.2026 обе системы делили префикс
 * /admin/notifications и имена маршрутов admin.notifications.*, из-за чего
 * колокольчик получал HTML вместо JSON, а его кнопка удаления била по
 * баннерам сайта. Центр разведён на /admin/notification-center.
 */
class NotificationController extends Controller
{
    /**
     * 📋 Список уведомлений
     */
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->filled('target')) {
            $query->where('target', $request->input('target'));
        }

        if ($request->filled('position')) {
            $query->byPosition($request->input('position'));
        }

        if ($request->filled('enabled')) {
            if ($request->input('enabled') === '1') {
                $query->enabled();
            } elseif ($request->input('enabled') === '0') {
                $query->where('enabled', false);
            }
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';

        $allowedSortFields = ['id', 'title', 'type', 'target', 'position', 'created_at', 'updated_at', 'priority', 'views_count'];
        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('created_at');
        }

        $notifications = $query->paginate(10)->withQueryString();

        // Сводка по всей таблице, а не по текущей странице выдачи
        $stats = [
            'total'    => Notification::count(),
            'enabled'  => Notification::where('enabled', true)->count(),
            'active'   => Notification::active()->count(),
            'views'    => (int) Notification::sum('views_count'),
        ];

        return view('Notifications::admin.index', compact('notifications', 'stats'));
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
     *
     * Правила берутся из NotificationRequest — те же, что и при обновлении.
     * Раньше store() валидировал свой укороченный набор: тип html отклонялся,
     * а priority, starts_at, ends_at и снятая галочка «Включено» молча
     * терялись, хотя форма их отправляла.
     */
    public function store(NotificationRequest $request)
    {
        $data = $this->prepare($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $notification = Notification::create($data);

        NotificationCreated::dispatch($notification);

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление создано.');
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
        $data = $this->prepare($request);
        $data['updated_by'] = auth()->id();

        $notification->update($data);

        NotificationUpdated::dispatch($notification);

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление обновлено.');
    }

    /**
     * 🗑️ Удаление уведомления
     */
    public function destroy(Notification $notification)
    {
        NotificationDeleted::dispatch($notification);
        $notification->delete();

        return redirect()->route('admin.notifications.index')
                         ->with('success', 'Уведомление удалено.');
    }

    /**
     * 🔁 Переключение включённости уведомления
     */
    public function toggle(Notification $notification)
    {
        $notification->enabled = !$notification->enabled;
        $notification->updated_by = auth()->id();
        $notification->save();

        NotificationUpdated::dispatch($notification);

        return redirect()->back()->with(
            'success',
            $notification->enabled ? 'Уведомление включено.' : 'Уведомление отключено.'
        );
    }

    /**
     * 📦 Массовые действия
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action'     => 'required|in:enable,disable,delete',
            'selected'   => 'required|array',
            'selected.*' => 'integer|exists:notifications,id',
        ], [
            'action.required'   => 'Выберите действие.',
            'action.in'         => 'Неизвестное действие.',
            'selected.required' => 'Отметьте хотя бы одно уведомление.',
        ]);

        $ids = $request->input('selected');
        $notifications = Notification::whereIn('id', $ids)->get();

        switch ($request->input('action')) {
            case 'delete':
                foreach ($notifications as $notification) {
                    NotificationDeleted::dispatch($notification);
                    $notification->delete();
                }
                $message = 'Удалено уведомлений: ' . $notifications->count();
                break;

            case 'enable':
            case 'disable':
                $enabled = $request->input('action') === 'enable';
                foreach ($notifications as $notification) {
                    // Через модель, а не массовым update: иначе не сработают
                    // события, а с ними и сброс кеша выдачи
                    $notification->update(['enabled' => $enabled, 'updated_by' => auth()->id()]);
                    NotificationUpdated::dispatch($notification);
                }
                $message = ($enabled ? 'Включено' : 'Отключено') . ' уведомлений: ' . $notifications->count();
                break;

            default:
                return back()->with('error', 'Неизвестное действие.');
        }

        return back()->with('success', $message);
    }

    /**
     * 👁️ Предпросмотр уведомления.
     *
     * Маршрут был объявлен и раньше, но вьюхи Notifications::admin.preview
     * не существовало — переход отдавал 500.
     */
    public function preview(Notification $notification)
    {
        return view('Notifications::admin.preview', compact('notification'));
    }

    /**
     * Приведение полей формы к тому, что ждёт БД.
     */
    private function prepare(NotificationRequest $request): array
    {
        $data = $request->validated();

        // Чекбокс не приходит вовсе, когда снят
        $data['enabled'] = $request->boolean('enabled');

        // Пустые строки полей формы должны стать NULL, а не ''
        foreach (['icon', 'route_filter', 'cookie_key', 'bg_color', 'text_color', 'starts_at', 'ends_at'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        $data['duration'] = (int) ($data['duration'] ?? 0);
        $data['priority'] = (int) ($data['priority'] ?? 0);

        return $data;
    }
}
