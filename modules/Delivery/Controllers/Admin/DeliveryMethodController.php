<?php

namespace Modules\Delivery\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Delivery\Models\DeliveryMethod;

class DeliveryMethodController extends Controller
{
    public function index()
    {
        $methods = DeliveryMethod::all();
        return view('Delivery::admin.index', compact('methods'));
    }

    public function create()
    {
        return view('Delivery::admin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'active'      => 'boolean',
        ]);

        DeliveryMethod::create([
            'title'       => $request->title,
            'description' => $request->description,
            'price'       => $request->price,
            'active'      => $request->boolean('active'),
        ]);

        return redirect()->route('admin.delivery.index')->with('success', 'Метод доставки добавлен');
    }

    public function edit(DeliveryMethod $delivery)
    {
        return view('Delivery::admin.edit', compact('delivery'));
    }

    public function update(Request $request, DeliveryMethod $delivery)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'active'      => 'boolean',
        ]);

        $delivery->update([
            'title'       => $request->title,
            'description' => $request->description,
            'price'       => $request->price,
            'active'      => $request->boolean('active'),
        ]);

        return redirect()->route('admin.delivery.index')->with('success', 'Метод доставки обновлён');
    }

    public function destroy(DeliveryMethod $delivery)
    {
        $delivery->delete();
        return back()->with('success', 'Метод доставки удалён');
    }
}
