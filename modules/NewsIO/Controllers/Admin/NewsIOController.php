<?php

namespace Modules\NewsIO\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Categories\Models\Category;
use Modules\News\Models\News;
use Modules\NewsIO\Http\Requests\ExportRequest;
use Modules\NewsIO\Http\Requests\ImportRequest;
use Modules\NewsIO\Services\Exporter;
use Modules\NewsIO\Services\Importer;

class NewsIOController extends Controller
{
    public function index()
    {
        // Категории для фильтра экспорта + счётчик новостей в каждой,
        // чтобы было видно, что реально попадёт в выгрузку.
        $categories = Category::orderBy('title')
            ->withCount('news')
            ->get(['id', 'title']);

        // Короткая сводка для шапки: сколько всего материалов и сколько из них
        // опубликовано. Раньше страница не показывала объём данных вообще.
        $stats = [
            'news'      => News::count(),
            'published' => News::where('published', true)->count(),
            'drafts'    => News::where('published', false)->count(),
            'cats'      => $categories->count(),
        ];

        return view('NewsIO::admin.index', compact('categories', 'stats'));
    }

    public function export(ExportRequest $request, Exporter $exporter)
    {
        $opts = $request->validated();
        $path = $exporter->export($opts); // относительный путь в storage
        return response()->download(Storage::path($path))->deleteFileAfterSend(true);
    }

    public function dryRun(ImportRequest $request, Importer $importer)
    {
        $opts = $request->validated();
        [$preview, $warnings] = $importer->dryRun($opts);
        return response()->json(compact('preview','warnings'));
    }

    public function import(ImportRequest $request, Importer $importer)
    {
        $opts = $request->validated();
        $result = $importer->import($opts);
        
        $message = "Импорт завершён: создано {$result['created']}, обновлено {$result['updated']}";
        if ($result['skipped'] > 0) {
            $message .= ", пропущено {$result['skipped']}";
        }
        
        if (!empty($result['errors']) && count($result['errors']) > 0) {
            $errorCount = count($result['errors']);
            $message .= ". Ошибок: {$errorCount}";
            // Показываем первые 5 ошибок
            $errorsPreview = array_slice($result['errors'], 0, 5);
            return back()
                ->with('success', $message)
                ->with('import_errors', $errorsPreview)
                ->with('import_errors_count', $errorCount);
        }
        
        return back()->with('success', $message);
    }
}
