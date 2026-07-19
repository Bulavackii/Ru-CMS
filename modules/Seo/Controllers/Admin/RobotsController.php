<?php

namespace Modules\Seo\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class RobotsController extends Controller
{
    /** Относительный путь от public/ */
    protected string $path = 'robots.txt';

    public function edit()
    {
        $file    = public_path($this->path);
        $content = File::exists($file) ? File::get($file) : $this->defaultRobots();

        // Нормализуем переносы для textarea
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return view('seo::admin.robots', compact('content'));
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'content' => 'required|string|max:10000',
        ]);

        // \r\n/\r -> \n
        $content = str_replace(["\r\n", "\r"], "\n", $data['content']);

        // Убираем управляющие символы (кроме \n и \t), затем гарантируем конечный \n
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $content) ?? '';
        $content = rtrim($content, "\n") . "\n";

        $file = public_path($this->path);

        try {
            File::ensureDirectoryExists(dirname($file), 0775, true);

            // Бэкап, если был файл
            $backup = null;
            if (File::exists($file)) {
                $backup = $file . '.bak';
                @File::copy($file, $backup);
            }

            // Атомарная запись: tmp + rename
            $tmp = $file . '.tmp';
            File::put($tmp, $content, true); // LOCK_EX
            @chmod($tmp, 0644);
            rename($tmp, $file);

            return back()->with('status', 'robots.txt сохранён');
        } catch (\Throwable $e) {
            // Попробуем откатиться на бэкап
            if (!empty($backup) && File::exists($backup)) {
                @File::copy($backup, $file);
            }
            report($e);
            return back()->with('status', 'Ошибка сохранения robots.txt: ' . $e->getMessage());
        }
    }

    /**
     * Дефолт под Рунет: Host (Яндекс) + Sitemap.
     */
    protected function defaultRobots(): string
    {
        $base    = rtrim((string) config('app.url'), '/');
        $host    = parse_url($base, PHP_URL_HOST) ?: request()->getHost();
        $sitemap = ($base ?: rtrim(request()->getSchemeAndHttpHost(), '/')) . '/sitemap.xml';

        return implode("\n", [
            'User-agent: *',
            'Disallow:',
            '',
            "Host: {$host}",
            "Sitemap: {$sitemap}",
            '',
        ]);
    }
}
