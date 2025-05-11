<?php

namespace Modules\Messages\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Messages\Models\Message;

class MessageController extends Controller
{
    public function index()
    {
        $messages = Message::with('user')->orderByDesc('created_at')->paginate(10);
        return view('messages::admin.index', compact('messages'));
    }

    public function create()
    {
        return view('messages::admin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        Message::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'body' => $request->body,
            'is_read' => false,
        ]);

        return redirect()->route('admin.messages.index')->with('success', 'Сообщение отправлено');
    }

    public function show(Message $message)
    {
        $message->update(['is_read' => true]);
        return view('messages::admin.show', compact('message'));
    }
}
