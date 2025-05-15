<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * 🔑 Форма смены пароля
     */
    public function edit()
    {
        return view('frontend.dashboard.password');
    }

    /**
     * 🔐 Обновление пароля
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Текущий пароль введён неверно']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Пароль успешно обновлён');
    }
}
