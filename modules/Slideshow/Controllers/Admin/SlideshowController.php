<?php

namespace Modules\Slideshow\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Slideshow\Models\Slideshow;
use Modules\Slideshow\Models\SlideshowItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SlideshowController extends Controller
{

    public function index()
    {
        $slideshows = Slideshow::withCount('items')->orderBy('created_at', 'desc')->paginate(10);

        return view('Slideshow::admin.index', compact('slideshows'));
    }

    /**
     * Форма добавления нового слайда
     */
    public function create(Request $request)
    {
        $slideshowId = $request->input('slideshow_id');

        // Проверим, что слайдшоу существует
        $slideshow = Slideshow::findOrFail($slideshowId);

        return view('Slideshow::admin.slide-create', compact('slideshowId'));
    }

    /**
     * Сохранение нового слайда
     */
    public function store(Request $request)
    {
        $request->validate([
            'slideshow_id' => 'required|exists:slideshows,id',
            'media' => 'required|file|mimes:jpeg,png,webp,mp4,webm|max:20480',
            'caption' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
        ]);

        $file = $request->file('media');
        $path = $file->store('slideshows', 'public');

        SlideshowItem::create([
            'slideshow_id' => $request->slideshow_id,
            'file_path' => $path,
            'media_type' => str_contains($file->getMimeType(), 'video') ? 'video' : 'image',
            'caption' => $request->caption,
            'order' => $request->order ?? 0,
        ]);

        return redirect()->route('admin.slideshow.edit', $request->slideshow_id)
            ->with('success', 'Слайд добавлен');
    }

    /**
     * Удаление слайда
     */
    public function destroy(Slideshow $slideshow)
    {
        $slideshow->items->each(function ($item) {
            Storage::disk('public')->delete($item->file_path);
            $item->delete();
        });

        $slideshow->delete();

        return redirect()->route('admin.slideshow.index')->with('success', 'Слайдшоу удалено!');
    }

    public function edit($id)
    {
        $slideshow = Slideshow::with('items')->findOrFail($id);
        return view('Slideshow::admin.edit', compact('slideshow'));
    }

    public function createSlideshow()
    {
        return view('Slideshow::admin.create');
    }

    public function storeSlideshow(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        Slideshow::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
        ]);

        return redirect()->route('admin.slideshow.index')->with('success', 'Слайдшоу создано!');
    }
}
