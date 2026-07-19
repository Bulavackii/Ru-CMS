<?php

namespace Modules\NewsIO\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Categories\Models\Category;
use Modules\NewsIO\Http\Requests\ExportRequest;
use Modules\NewsIO\Http\Requests\ImportRequest;
use Modules\NewsIO\Services\Exporter;
use Modules\NewsIO\Services\Importer;

class NewsIOController extends Controller
{
    public function index()
    {
        // было: ->get(['id','title','slug'])
        $categories = Category::orderBy('title')->get(['id','title']);
        return view('NewsIO::admin.index', compact('categories'));
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
