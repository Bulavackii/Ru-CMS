<?php

namespace Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Payments\Models\PaymentMethod;

class PaymentController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('id', 'desc')->get();
        return view('Payments::admin.index', compact('methods'));
    }

    public function create()
    {
        return view('Payments::admin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:offline,online',
            'active'      => 'boolean',
        ]);

        PaymentMethod::create([
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'active'      => $request->boolean('active'),
            'settings'    => [],
        ]);

        return redirect()->route('admin.payments.index')->with('success', 'Способ оплаты добавлен');
    }

    public function edit($id)
    {
        $method = PaymentMethod::findOrFail($id);
        return view('Payments::admin.edit', compact('method'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:offline,online',
            'active'      => 'boolean',
        ]);

        $method = PaymentMethod::findOrFail($id);
        $method->update([
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'active'      => $request->boolean('active'),
        ]);

        return redirect()->route('admin.payments.index')->with('success', 'Способ оплаты обновлён');
    }

    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()->route('admin.payments.index')->with('success', 'Способ оплаты удалён');
    }
}
