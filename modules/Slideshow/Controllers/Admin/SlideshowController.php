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
     * 📋 Отображение списка всех слайдшоу
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $q         = trim((string)$request->input('q', ''));
        $position  = trim((string)$request->input('position', ''));
        $published = $request->input('published', '');
        $perPage   = (int)$request->integer('per_page', 25);

        $query = \Modules\Slideshow\Models\Slideshow::with('items');

        if ($q !== '') {
            $query->where('title', 'like', "%{$q}%");
        }
        if ($position !== '') {
            $query->where('position', $position);
        }
        if ($published !== '') {
            $query->where('published', $published === '1');
        }

        $slideshows = $query
            ->orderBy('id')                           // или по updated_at desc — как удобнее
            ->paginate($perPage)
            ->withQueryString();                      // <— сохраняет фильтры при пагинации

        return view('Slideshow::admin.index', compact('slideshows'));
    }

    /**
     * ➕ Форма создания нового слайдшоу
     */
    public function createSlideshow()
    {
        return view('Slideshow::admin.create');
    }

    /**
     * 💾 Сохранение нового слайдшоу
     */
    public function storeSlideshow(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'position' => 'required|in:top,bottom',
        ]);

        Slideshow::create([
            'title'     => $request->title,
            'slug'      => Str::slug($request->title) . '-' . uniqid(),
            'position'  => $request->position,
            'published' => $request->boolean('published', false),
        ]);

        return redirect()
            ->route('admin.slideshow.index')
            ->with('success', 'Слайдшоу успешно создано!');
    }

    /**
     * ✏️ Редактирование слайдшоу и добавление слайдов
     */
    public function edit($id)
    {
        $slideshow = Slideshow::with('items')->findOrFail($id);
        return view('Slideshow::admin.edit', compact('slideshow'));
    }

    /**
     * ⬆️ Загрузка и сохранение нового слайда
     */
    public function store(Request $request)
    {
        $request->validate([
            'slideshow_id' => 'required|exists:slideshows,id',
            'media'        => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,mp4,webm',
                'max:10240', // 10MB вместо 20MB
            ],
            'caption'      => 'nullable|string|max:255',
            'alt_text'     => 'nullable|string|max:255',
            'link'         => 'nullable|url|max:500',
            'order'        => 'nullable|integer',
            'position'     => 'nullable|in:top,bottom',
            'text_position' => 'nullable|in:top-left,top-center,top-right,bottom-left,bottom-center,bottom-right,center',
            'text_color'   => 'nullable|string|max:7',
            'background_color' => 'nullable|string|max:7',
        ]);

        $file = $request->file('media');
        $isImage = str_starts_with($file->getMimeType(), 'image/');
        
        // Валидация размеров для изображений
        if ($isImage) {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                
                if ($width < 800 || $height < 400) {
                    return redirect()
                        ->route('admin.slideshow.edit', $request->slideshow_id)
                        ->withErrors(['media' => 'Минимальный размер изображения: 800x400px. Текущий: ' . $width . 'x' . $height . 'px']);
                }
            }
        }

        $path = $file->store('slideshows', 'public');

        SlideshowItem::create([
            'slideshow_id'     => $request->slideshow_id,
            'file_path'       => $path,
            'media_type'      => $isImage ? 'image' : 'video',
            'caption'         => $request->caption,
            'alt_text'        => $request->alt_text,
            'link'            => $request->link,
            'order'           => $request->order ?? 0,
            'text_position'   => $request->text_position ?? 'bottom-right',
            'text_color'      => $request->text_color,
            'background_color' => $request->background_color,
        ]);

        if ($request->filled('position')) {
            $slideshow = Slideshow::find($request->slideshow_id);
            $slideshow->position = $request->position;
            $slideshow->save();
        }

        return redirect()
            ->route('admin.slideshow.edit', $request->slideshow_id)
            ->with('success', 'Слайд добавлен');
    }

    /**
     * ❌ Удаление отдельного слайда
     */
    public function deleteSlide($id)
    {
        $slide = SlideshowItem::findOrFail($id);

        Storage::disk('public')->delete($slide->file_path);
        $slideshowId = $slide->slideshow_id;

        $slide->delete();

        return redirect()
            ->route('admin.slideshow.edit', $slideshowId)
            ->with('success', 'Слайд удалён');
    }

    /**
     * 🗑️ Удаление всего слайдшоу и его слайдов
     */
    public function destroy(Slideshow $slideshow)
    {
        $slideshow->items->each(function ($item) {
            Storage::disk('public')->delete($item->file_path);
            $item->delete();
        });

        $slideshow->delete();

        return redirect()
            ->route('admin.slideshow.index')
            ->with('success', 'Слайдшоу удалено');
    }

    /**
     * 🔃 Сохранение нового порядка слайдов (drag-n-drop)
     */
    public function sort(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:slideshow_items,id',
        ]);

        foreach ($request->input('order') as $index => $id) {
            SlideshowItem::where('id', $id)->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }


    /**
     * 🗑️ Массовое удаление слайдшоу
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:slideshows,id',
        ]);

        $slideshows = Slideshow::whereIn('id', $request->ids)->get();
        $count = 0;

        foreach ($slideshows as $slideshow) {
            // Удаляем файлы слайдов
            $slideshow->items->each(function ($item) {
                Storage::disk('public')->delete($item->file_path);
            });
            $slideshow->delete();
            $count++;
        }

        return redirect()
            ->route('admin.slideshow.index')
            ->with('success', "Удалено слайдшоу: {$count}");
    }

    public function updateSlide(Request $request, $id)
    {
        $slide = SlideshowItem::findOrFail($id);

        $request->validate([
            'caption'         => 'nullable|string|max:255',
            'alt_text'        => 'nullable|string|max:255',
            'link'            => 'nullable|url|max:500',
            'text_position'  => 'nullable|in:top-left,top-center,top-right,bottom-left,bottom-center,bottom-right,center',
            'text_color'      => 'nullable|string|max:7',
            'background_color' => 'nullable|string|max:7',
        ]);

        $slide->update([
            'caption'         => $request->caption,
            'alt_text'       => $request->alt_text,
            'link'           => $request->link,
            'text_position'  => $request->text_position ?? $slide->text_position,
            'text_color'     => $request->text_color,
            'background_color' => $request->background_color,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * 🔄 Переключение статуса публикации
     */
    public function togglePublished($id)
    {
        $slideshow = Slideshow::findOrFail($id);
        $slideshow->published = !$slideshow->published;
        $slideshow->save();

        return redirect()
            ->route('admin.slideshow.index')
            ->with('success', 'Статус публикации изменён');
    }

    /**
     * ✏️ Обновление слайдшоу
     */
    public function update(Request $request, $id)
    {
        $slideshow = Slideshow::findOrFail($id);

        $request->validate([
            'title'            => 'required|string|max:255',
            'position'         => 'required|in:top,bottom',
            'published'       => 'boolean',
            'description'      => 'nullable|string',
            'autoplay_delay'   => 'nullable|integer|min:1000|max:30000',
            'transition_effect' => 'nullable|in:slide,fade,cube,coverflow,flip',
            'height'           => 'nullable|string|max:50',
            'show_pagination'  => 'boolean',
            'show_navigation'  => 'boolean',
        ]);

        $slideshow->update([
            'title'            => $request->title,
            'position'         => $request->position,
            'published'        => $request->boolean('published', false),
            'description'      => $request->description,
            'autoplay_delay'   => $request->autoplay_delay ?? 5000,
            'transition_effect' => $request->transition_effect ?? 'slide',
            'height'           => $request->height,
            'show_pagination'  => $request->boolean('show_pagination', true),
            'show_navigation'  => $request->boolean('show_navigation', true),
        ]);

        return redirect()
            ->route('admin.slideshow.index')
            ->with('success', 'Слайдшоу обновлено');
    }

    /**
     * 👁️ Предпросмотр слайдшоу
     */
    public function preview($id)
    {
        $slideshow = Slideshow::with(['items' => function ($q) {
            $q->orderBy('order');
        }])->findOrFail($id);

        return view('Slideshow::admin.preview', compact('slideshow'));
    }
}
