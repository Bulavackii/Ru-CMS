<?php

namespace Modules\Messages\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Messages\Models\Message;
use App\Models\User;

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
    public function index()
    {
        // 📨 Загрузка сообщений, где пользователь — отправитель или получатель
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($query) {
                $query->where('user_id', Auth::id()) // исходящие
                      ->orWhere('to_user_id', Auth::id()); // входящие
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('messages::admin.index', compact('messages'));
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
        $request->validate([
            'subject'     => 'required|string|max:255', // Тема
            'body'        => 'required|string',          // Содержимое
            'to_user_id'  => 'required|exists:users,id', // Получатель
        ]);

        // ✉️ Сохраняем сообщение в базу данных
        Message::create([
            'user_id'     => Auth::id(),             // ID отправителя
            'to_user_id'  => $request->to_user_id,   // ID получателя
            'subject'     => $request->subject,      // Тема
            'body'        => $request->body,         // Текст
            'is_read'     => false,                  // По умолчанию — не прочитано
        ]);

        // 🔁 Перенаправление с уведомлением об успехе
        return redirect()->route('admin.messages.index')->with('success', 'Сообщение отправлено');
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

        // ✅ Если сообщение адресовано текущему пользователю, помечаем как прочитанное
        if ($message->to_user_id === Auth::id()) {
            $message->update(['is_read' => true]);
        }

        return view('messages::admin.show', compact('message'));
    }
}
