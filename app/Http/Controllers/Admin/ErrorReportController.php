<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class ErrorReportController extends Controller
{
    /**
     * 🖊️ Страница — форма отправки отчёта
     */
    public function form()
    {
        return view('admin.error.report-error');
    }

    /**
     * 📧 Обработка отправки отчёта
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:10',
            'email' => 'nullable|email',
            'file' => 'nullable|file|max:2048',
        ]);

        $data = [
            'message' => $request->input('message'),
            'email' => $request->input('email'),
            'user' => $request->user(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->headers->get('referer'),
        ];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('error-attachments', 'public');
            $data['file_path'] = Storage::disk('public')->path($path);
        }

        Mail::raw(
            view('admin.error.mail', $data)->render(),
            function ($message) use ($data) {
                $message->to(config('mail.from.address'), 'Support')
                        ->subject('Ошибка на сайте')
                        ->replyTo($data['email'] ?? config('mail.from.address'));

                if (!empty($data['file_path'])) {
                    $message->attach($data['file_path']);
                }
            }
        );

        return back()->with('success', 'Ваше сообщение отправлено. Спасибо!');
    }

    /**
     * 🌍 Страница геолокации
     */
    public function geolocation(Request $request)
    {
        $ip = $request->ip();
        $location = Location::get($ip);

        return view('admin.error.geolocation', [
            'ip' => $ip,
            'location' => $location,
            'userAgent' => $request->userAgent(),
            'language' => $request->server('HTTP_ACCEPT_LANGUAGE'),
            'timestamp' => now(),
        ]);
    }

    /**
     * 💻 Информация о системе
     */
    public function systemInfo()
    {
        return view('admin.error.system-info');
    }
}
