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
    /**
     * Список всех слайдшоу
     */
    public function index()
    {
        $slideshows = Slideshow::withCount('items')->orderBy('created_at', 'desc')->paginate(10);
        return view('Slideshow::admin.index', compact('slideshows'));
    }

    /**
     * Форма создания слайдшоу
     */
    public function createSlideshow()
    {
        return view('Slideshow::admin.create');
    }

    /**
     * Сохранение нового слайдшоу
     */
    public function storeSlideshow(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'position' => 'required|in:top,bottom',
        ]);

        Slideshow::create([
            'title'    => $request->title,
            'slug'     => Str::slug($request->title) . '-' . uniqid(),
            'position' => $request->position,
        ]);

        return redirect()->route('admin.slideshow.index')->with('success', 'Слайдшоу создано!');
    }

    /**
     * Страница редактирования слайдшоу и добавления слайдов
     */
    public function edit($id)
    {
        $slideshow = Slideshow::with('items')->findOrFail($id);
        return view('Slideshow::admin.edit', compact('slideshow'));
    }

    /**
     * Сохранение нового слайда
     */
    public function store(Request $request)
    {
        $request->validate([
            'slideshow_id' => 'required|exists:slideshows,id',
            'media'        => 'required|file|mimes:jpeg,png,webp,mp4,webm|max:20480',
            'caption'      => 'nullable|string|max:255',
            'order'        => 'nullable|integer',
            'position'     => 'nullable|in:top,bottom',
        ]);

        $file = $request->file('media');
        $path = $file->store('slideshows', 'public');

        SlideshowItem::create([
            'slideshow_id' => $request->slideshow_id,
            'file_path'    => $path,
            'media_type'   => str_contains($file->getMimeType(), 'video') ? 'video' : 'image',
            'caption'      => $request->caption,
            'order'        => $request->order ?? 0,
        ]);

        if ($request->filled('position')) {
            $slideshow = Slideshow::find($request->slideshow_id);
            $slideshow->position = $request->position;
            $slideshow->save();
        }

        return redirect()->route('admin.slideshow.edit', $request->slideshow_id)
            ->with('success', 'Слайд добавлен');
    }

    /**
     * Удаление всего слайдшоу
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

    /**
     * Удаление отдельного слайда
     */
    public function deleteSlide($id)
    {
        $slide = SlideshowItem::findOrFail($id);

        Storage::disk('public')->delete($slide->file_path);
        $slideshowId = $slide->slideshow_id;

        $slide->delete();

        return redirect()->route('admin.slideshow.edit', $slideshowId)
            ->with('success', 'Слайд удалён');
    }

    /**
     * Массовое удаление слайдшоу и их слайдов
     */
    public function bulkDelete(Request $request)
{
    dd($request->all()); // Для отладки
}

}
