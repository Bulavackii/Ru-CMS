<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('frontend.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user->name  = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Данные профиля обновлены.');
    }
}
