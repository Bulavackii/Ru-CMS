<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        // Только для юр. лиц
        if (!$user->is_company) {
            return redirect()->route('dashboard')->with('error', 'Раздел доступен только юридическим лицам.');
        }

        return view('frontend.dashboard.organization', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user->is_company) {
            return redirect()->route('dashboard')->with('error', 'Раздел доступен только юридическим лицам.');
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'inn' => 'required|string|max:20',
            'ogrn' => 'required|string|max:20',
        ]);

        $user->company_name = $request->input('company_name');
        $user->inn = $request->input('inn');
        $user->ogrn = $request->input('ogrn');
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Организационные данные обновлены.');
    }
}
