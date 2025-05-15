<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountSettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('admin.account.settings', [
            'user' => $user,
            'dbVersion' => DB::selectOne('select version() as version')->version ?? 'N/A',
        ]);
    }
}
