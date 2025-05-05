<?php

namespace Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Notifications\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::latest()->paginate(10);
        return view('Notifications::admin.index', compact('notifications'));
    }

    public function create()
    {
        return view('Notifications::admin.create');
    }

    public function store(Request $request)
    {
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

        $validated['enabled'] = true;

        Notification::create($validated);

        return redirect()->route('admin.notifications.index')->with('success', 'Уведомление создано!');
    }

    public function edit(Notification $notification)
    {
        return view('Notifications::admin.edit', compact('notification'));
    }

    public function update(Request $request, Notification $notification)
    {
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

        $notification->update($validated);

        return redirect()->route('admin.notifications.index')->with('success', 'Уведомление обновлено!');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'Уведомление удалено!');
    }

    public function toggle(Notification $notification)
    {
        $notification->enabled = !$notification->enabled;
        $notification->save();

        return redirect()->back()->with('success', 'Статус уведомления обновлён.');
    }
}
