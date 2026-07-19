<?php

namespace Modules\Messages\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Modules\Messages\Models\Message;
use Modules\Messages\Models\MessageAttachment;
use App\Models\User;
use App\Services\NotificationService;

/**
 * 📬 Контроллер внутренних сообщений между администраторами
 *
 * Позволяет:
 * 🔸 Просматривать список сообщений (входящие/исходящие)
 * 🔸 Создавать и отправлять сообщения другим администраторам
 * 🔸 Читать и помечать как прочитанные
 */
class MessageController extends Controller
{
    /**
     * 🗂️ Список всех сообщений (входящие и исходящие)
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all, inbox, sent, important, archived
        $search = $request->get('search');
        $userId = Auth::id();

        $query = Message::with(['sender', 'receiver', 'attachments'])
            ->notArchived()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('to_user_id', $userId);
            });

        // Фильтрация
        switch ($filter) {
            case 'inbox':
                $query->inbox($userId);
                break;
            case 'sent':
                $query->sent($userId);
                break;
            case 'important':
                $query->important();
                break;
            case 'archived':
                $query->archived();
                break;
            case 'unread':
                $query->unread()->inbox($userId);
                break;
        }

        // Поиск
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $messages = $query->orderByDesc('is_important')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends(['filter' => $filter, 'search' => $search]);

        // Счётчики
        $counts = [
            'all' => Message::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('to_user_id', $userId);
            })->notArchived()->count(),
            'inbox' => Message::inbox($userId)->notArchived()->count(),
            'sent' => Message::sent($userId)->notArchived()->count(),
            'unread' => Message::inbox($userId)->unread()->notArchived()->count(),
            'important' => Message::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('to_user_id', $userId);
            })->important()->notArchived()->count(),
        ];

        return view('messages::admin.index', compact('messages', 'filter', 'search', 'counts'));
    }

    /**
     * 📝 Форма создания нового сообщения
     */
    public function create()
    {
        // 👥 Получаем всех администраторов (можно исключить себя, если нужно)
        $admins = User::where('is_admin', true)->get();

        return view('messages::admin.create', compact('admins'));
    }

    /**
     * 💾 Отправка нового сообщения
     */
    public function store(Request $request)
    {
        // ✅ Валидация полей
        $validated = $request->validate([
            'subject'     => 'required|string|max:255',
            'body'        => 'required|string',
            'to_user_id'  => 'required|exists:users,id',
            'parent_id'   => 'nullable|exists:messages,id',
            'is_important' => 'nullable|boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // Максимум 10MB
        ]);

        // ✉️ Сохраняем сообщение в базу данных
        $message = Message::create([
            'user_id'      => Auth::id(),
            'to_user_id'   => $validated['to_user_id'],
            'parent_id'    => $validated['parent_id'] ?? null,
            'subject'      => $validated['subject'],
            'body'         => $validated['body'],
            'is_read'      => false,
            'is_important' => $request->has('is_important'),
        ]);

        // Обработка вложений
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('message-attachments', 'public');
                
                MessageAttachment::create([
                    'message_id' => $message->id,
                    'filename'   => $file->getClientOriginalName(),
                    'path'       => $path,
                    'mime_type'  => $file->getMimeType(),
                    'size'       => $file->getSize(),
                ]);
            }
        }

        // Отправка уведомления получателю
        $receiver = User::find($validated['to_user_id']);
        if ($receiver) {
            try {
                app(NotificationService::class)->info(
                    'Новое сообщение',
                    "От {$message->sender->name}: {$message->subject}",
                    $receiver->id,
                    route('admin.messages.show', $message),
                    'Открыть'
                );
            } catch (\Exception $e) {
                // Игнорируем ошибки уведомлений
            }
        }

        return redirect()->route('admin.messages.index', ['filter' => 'sent'])
            ->with('success', 'Сообщение отправлено');
    }

    /**
     * 👁️ Просмотр конкретного сообщения
     */
    public function show(Message $message)
    {
        // 🔐 Защита: разрешаем видеть только отправителю или получателю
        if ($message->user_id !== Auth::id() && $message->to_user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён.');
        }

        // Загружаем цепочку переписки
        $message->load(['sender', 'receiver', 'attachments', 'replies.sender', 'replies.receiver']);
        
        // Если есть родитель, загружаем всю цепочку
        $thread = collect();
        if ($message->parent_id) {
            $parent = Message::with(['sender', 'receiver'])->find($message->parent_id);
            while ($parent) {
                $thread->prepend($parent);
                $parent = $parent->parent_id ? Message::with(['sender', 'receiver'])->find($parent->parent_id) : null;
            }
        }
        $thread->push($message);
        $thread = $thread->merge($message->replies);

        // ✅ Если сообщение адресовано текущему пользователю, помечаем как прочитанное
        if ($message->to_user_id === Auth::id() && !$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('messages::admin.show', compact('message', 'thread'));
    }

    /**
     * 📝 Форма ответа на сообщение
     */
    public function reply(Message $message)
    {
        // 🔐 Защита
        if ($message->user_id !== Auth::id() && $message->to_user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён.');
        }

        $admins = User::where('is_admin', true)->get();
        
        // Определяем получателя ответа
        $recipient = $message->user_id === Auth::id() 
            ? $message->receiver 
            : $message->sender;

        return view('messages::admin.create', [
            'admins' => $admins,
            'replyTo' => $message,
            'recipient' => $recipient,
        ]);
    }

    /**
     * 🗑️ Удаление сообщения
     */
    public function destroy(Message $message)
    {
        // 🔐 Защита
        if ($message->user_id !== Auth::id() && $message->to_user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён.');
        }

        // Удаляем вложения
        foreach ($message->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
        }

        $message->delete();

        return redirect()->route('admin.messages.index')
            ->with('success', 'Сообщение удалено');
    }

    /**
     * ⭐ Пометить как важное/неважное
     */
    public function toggleImportant(Message $message)
    {
        if ($message->user_id !== Auth::id() && $message->to_user_id !== Auth::id()) {
            abort(403);
        }

        $message->update(['is_important' => !$message->is_important]);

        return back()->with('success', $message->is_important 
            ? 'Сообщение помечено как важное' 
            : 'Сообщение убрано из важных');
    }

    /**
     * 📬 Пометить как прочитанное/непрочитанное
     */
    public function toggleRead(Message $message)
    {
        if ($message->to_user_id !== Auth::id()) {
            abort(403);
        }

        $message->update(['is_read' => !$message->is_read]);

        return back()->with('success', $message->is_read 
            ? 'Сообщение помечено как прочитанное' 
            : 'Сообщение помечено как непрочитанное');
    }

    /**
     * 📦 Архивирование сообщения
     */
    public function archive(Message $message)
    {
        if ($message->user_id !== Auth::id() && $message->to_user_id !== Auth::id()) {
            abort(403);
        }

        $message->update([
            'archived_at' => $message->archived_at ? null : now()
        ]);

        return back()->with('success', $message->archived_at 
            ? 'Сообщение заархивировано' 
            : 'Сообщение восстановлено из архива');
    }

    /**
     * 📦 Массовые операции
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Выберите хотя бы одно сообщение');
        }

        $messages = Message::whereIn('id', $ids)
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhere('to_user_id', Auth::id());
            })
            ->get();

        $count = 0;
        foreach ($messages as $message) {
            switch ($action) {
                case 'delete':
                    foreach ($message->attachments as $attachment) {
                        Storage::disk('public')->delete($attachment->path);
                        $attachment->delete();
                    }
                    $message->delete();
                    $count++;
                    break;
                case 'read':
                    if ($message->to_user_id === Auth::id()) {
                        $message->update(['is_read' => true]);
                        $count++;
                    }
                    break;
                case 'unread':
                    if ($message->to_user_id === Auth::id()) {
                        $message->update(['is_read' => false]);
                        $count++;
                    }
                    break;
                case 'important':
                    $message->update(['is_important' => true]);
                    $count++;
                    break;
                case 'unimportant':
                    $message->update(['is_important' => false]);
                    $count++;
                    break;
                case 'archive':
                    $message->update(['archived_at' => now()]);
                    $count++;
                    break;
                case 'unarchive':
                    $message->update(['archived_at' => null]);
                    $count++;
                    break;
            }
        }

        $actions = [
            'delete' => 'удалено',
            'read' => 'помечено как прочитанное',
            'unread' => 'помечено как непрочитанное',
            'important' => 'помечено как важное',
            'unimportant' => 'убрано из важных',
            'archive' => 'заархивировано',
            'unarchive' => 'восстановлено из архива',
        ];

        return back()->with('success', "{$count} сообщений {$actions[$action]}");
    }

    /**
     * 📥 Скачивание вложения
     */
    public function downloadAttachment(MessageAttachment $attachment)
    {
        // Проверяем доступ к сообщению
        $message = $attachment->message;
        if ($message->user_id !== Auth::id() && $message->to_user_id !== Auth::id()) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk('public')->download($attachment->path, $attachment->filename);
    }
}
