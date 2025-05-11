<?php

namespace Modules\Messages\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Messages\Models\Message;
use App\Models\User;

class MessageController extends Controller
{
    public function index()
    {
        // Показываем входящие и исходящие сообщения текущего админа
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                      ->orWhere('to_user_id', Auth::id());
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('messages::admin.index', compact('messages'));
    }

    public function create()
    {
        // Список всех администраторов, включая себя
        $admins = User::where('is_admin', true)->get();

        return view('messages::admin.create', compact('admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'     => 'required|string|max:255',
            'body'        => 'required|string',
            'to_user_id'  => 'required|exists:users,id',
        ]);

        Message::create([
            'user_id'     => Auth::id(), // отправитель
            'to_user_id'  => $request->to_user_id, // получатель
            'subject'     => $request->subject,
            'body'        => $request->body,
            'is_read'     => false,
        ]);

        return redirect()->route('admin.messages.index')->with('success', 'Сообщение отправлено');
    }

    public function show(Message $message)
    {
        // Отмечаем как прочитанное, если ты — получатель
        if ($message->to_user_id === Auth::id()) {
            $message->update(['is_read' => true]);
        }

        return view('messages::admin.show', compact('message'));
    }
}
