<?php

namespace Modules\Visual\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Modules\Visual\Models\Fragment;
use Modules\Visual\Models\Revision;

class FragmentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Fragment::query();

        // Поиск
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Фильтр по зоне
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Фильтр по типу
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $fragments = $query->latest()->paginate(20)->withQueryString();
        return view('Visual::admin.fragments.index', compact('fragments'));
    }

    /** Поддерживает пресеты ?preset=header|footer. */
    public function create(Request $request)
    {
        $preset = (string) $request->query('preset', '');

        if (in_array($preset, ['header', 'footer'], true)) {
            $slug = $preset === 'header' ? 'site-header' : 'site-footer';
            if ($existing = Fragment::where('slug', $slug)->first()) {
                return redirect()
                    ->route('admin.visual.fragments.edit', $existing)
                    ->with('success', 'Уже создан — открыли на редактирование.');
            }
        }

        $fragment = new Fragment([
            'is_active' => true,
            'type'      => 'blade',
        ]);

        if ($preset === 'header') {
            $fragment->fill(['title' => 'Шапка сайта', 'slug' => 'site-header', 'zone' => 'header']);
        } elseif ($preset === 'footer') {
            $fragment->fill(['title' => 'Подвал сайта', 'slug' => 'site-footer', 'zone' => 'footer']);
        }

        return view('Visual::admin.fragments.editor', compact('fragment'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->applyReservedGuard($data, null);
        $data['updated_by'] = Auth::id();
        $data['type'] = $data['type'] ?? 'blade';

        $fragment = Fragment::create($data);
        $this->renderToCache($fragment);
        $fragment->save();
        
        // Сохраняем начальную ревизию
        $this->saveRevision($fragment);

        return redirect()->route('admin.visual.fragments.edit', $fragment)->with('success', 'Фрагмент создан');
    }

    public function edit(Fragment $fragment)
    {
        return view('Visual::admin.fragments.editor', compact('fragment'));
    }

    public function update(Request $request, Fragment $fragment)
    {
        $data = $this->validated($request, $fragment->id);
        $this->applyReservedGuard($data, $fragment);
        $data['updated_by'] = Auth::id();
        $data['type'] = $data['type'] ?? ($fragment->type ?: 'blade');

        $fragment->fill($data);
        $this->renderToCache($fragment);
        $fragment->save();
        
        // Сохраняем ревизию при обновлении
        $this->saveRevision($fragment);

        return back()->with('success', 'Фрагмент обновлён');
    }

    public function destroy(Fragment $fragment)
    {
        $fragment->delete();
        return back()->with('success', 'Фрагмент удалён');
    }

    /** Кнопка «Пересобрать HTML». */
    public function rebuild(Fragment $fragment)
    {
        $this->renderToCache($fragment);
        $fragment->save();
        
        // Инвалидация кэша фрагмента
        Cache::forget("fragment_html_{$fragment->slug}");
        
        return back()->with('success', 'HTML фрагмента пересобран');
    }

    /**
     * 🔄 Массовое переключение фрагментов
     */
    public function bulkToggle(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:visual_fragments,id',
            'action' => 'required|in:enable,disable',
        ]);

        $fragments = Fragment::whereIn('id', $request->ids)->get();
        $count = 0;

        foreach ($fragments as $fragment) {
            // Пропускаем системные фрагменты
            if (in_array($fragment->slug, ['site-header', 'site-footer'], true)) {
                continue;
            }
            
            $fragment->is_active = $request->action === 'enable';
            $fragment->save();
            $count++;
        }

        return redirect()->route('admin.visual.fragments.index')
            ->with('success', "Обработано фрагментов: {$count}");
    }

    /**
     * 🔄 Массовая пересборка HTML
     */
    public function bulkRebuild(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:visual_fragments,id',
        ]);

        $fragments = Fragment::whereIn('id', $request->ids)->get();
        
        foreach ($fragments as $fragment) {
            $this->renderToCache($fragment);
            $fragment->save();
            Cache::forget("fragment_html_{$fragment->slug}");
        }

        return redirect()->route('admin.visual.fragments.index')
            ->with('success', "Пересобрано фрагментов: {$fragments->count()}");
    }

    /**
     * 📋 Дублирование фрагмента
     */
    public function duplicate(Fragment $fragment)
    {
        $newFragment = $fragment->replicate();
        $newFragment->title = $fragment->title . ' (копия)';
        $newFragment->slug = $fragment->slug . '-copy-' . uniqid();
        $newFragment->is_active = false;
        $newFragment->html_cached = null; // Пересоберем при сохранении
        $newFragment->save();

        $this->renderToCache($newFragment);
        $newFragment->save();

        return redirect()->route('admin.visual.fragments.edit', $newFragment)
            ->with('success', 'Фрагмент скопирован');
    }

    /** Компиляция HTML: сначала blade `visual/fragments/{slug}.blade.php`, иначе fallback. */
    protected function renderToCache(Fragment $fragment): void
    {
        $viewName = 'visual.fragments.' . $fragment->slug;

        if (View::exists($viewName)) {
            $fragment->html_cached = view($viewName, ['fragment' => $fragment])->render();
        } else {
            $title = e($fragment->title);
            $fragment->html_cached =
                "<div class=\"visual-fragment\" data-fragment=\"{$fragment->slug}\"><strong>{$title}</strong></div>";
        }
    }

    /**
     * 💾 Сохранение ревизии фрагмента
     */
    protected function saveRevision(Fragment $fragment): void
    {
        // Ограничиваем количество ревизий (храним последние 50)
        $maxRevisions = 50;
        $revisionsCount = Revision::where('target_type', Fragment::class)
            ->where('target_id', $fragment->id)
            ->count();
        
        if ($revisionsCount >= $maxRevisions) {
            $oldest = Revision::where('target_type', Fragment::class)
                ->where('target_id', $fragment->id)
                ->oldest()
                ->first();
            if ($oldest) {
                $oldest->delete();
            }
        }

        Revision::create([
            'target_type' => Fragment::class,
            'target_id' => $fragment->id,
            'snapshot' => [
                'title' => $fragment->title,
                'slug' => $fragment->slug,
                'type' => $fragment->type,
                'zone' => $fragment->zone,
                'schema' => $fragment->schema,
                'data' => $fragment->data,
                'css_inline' => $fragment->css_inline,
                'is_active' => $fragment->is_active,
            ],
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * ⏪ Откат фрагмента к предыдущей версии
     */
    public function revert(Fragment $fragment, $revisionId)
    {
        $revision = Revision::where('target_type', Fragment::class)
            ->where('target_id', $fragment->id)
            ->findOrFail($revisionId);

        $snapshot = $revision->snapshot;
        
        $fragment->fill($snapshot);
        $this->renderToCache($fragment);
        $fragment->save();
        
        // Сохраняем новую ревизию после отката
        $this->saveRevision($fragment);

        return back()->with('success', 'Фрагмент откачен к версии от ' . $revision->created_at->format('d.m.Y H:i'));
    }

    /**
     * 📜 История версий фрагмента
     */
    public function history(Fragment $fragment)
    {
        $revisions = Revision::where('target_type', Fragment::class)
            ->where('target_id', $fragment->id)
            ->with('creator')
            ->latest()
            ->paginate(20);

        return view('Visual::admin.fragments.history', compact('fragment', 'revisions'));
    }

    /** Валидация + нормализация JSON-полей. */
    protected function validated(Request $request, ?int $id = null): array
    {
        $rules = [
            'title'       => ['required','string','max:255'],
            'slug'        => ['required','string','max:255','alpha_dash', Rule::unique('visual_fragments','slug')->ignore($id)],
            'zone'        => ['nullable', Rule::in(['header','footer','custom'])],
            'type'        => ['nullable','string','max:100'],
            'is_active'   => ['sometimes','boolean'],
            'schema'      => ['nullable'],
            'data'        => ['nullable'],
            'css_inline'  => ['nullable','string'],
            'html_cached' => ['nullable','string'],
        ];

        $data = $request->validate($rules);

        foreach (['schema','data'] as $jsonField) {
            if (!array_key_exists($jsonField, $data)) { $data[$jsonField] = []; continue; }
            if (is_string($data[$jsonField]) && $data[$jsonField] !== '') {
                $decoded = json_decode($data[$jsonField], true);
                $data[$jsonField] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($data[$jsonField])) {
                $data[$jsonField] = [];
            }
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        return $data;
    }

    /** Закрепляем slug/zone для системных фрагментов. */
    protected function applyReservedGuard(array &$data, ?Fragment $existing = null): void
    {
        if ($existing && in_array($existing->slug, ['site-header','site-footer'], true)) {
            $data['slug'] = $existing->slug;
        }

        $slug = $data['slug'] ?? ($existing?->slug);
        if ($slug === 'site-header')   $data['zone'] = 'header';
        elseif ($slug === 'site-footer') $data['zone'] = 'footer';
    }
}
