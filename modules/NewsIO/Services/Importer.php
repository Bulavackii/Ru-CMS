<?php

namespace Modules\NewsIO\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Categories\Models\Category;
use Modules\News\Models\News;

class Importer
{
    public function dryRun(array $opts): array
    {
        [$items, $media] = $this->read($opts['file']);
        $summary = $this->summarize($items, $opts);

        // Раньше вторым элементом возвращался ВЕСЬ массив записей, и контроллер
        // отдавал его как "warnings": настоящих предупреждений пользователь не
        // видел, а по сети зря гонялся полный дамп файла. Теперь — реальные
        // проблемы, которые всплывут при импорте.
        return [$summary, $this->collectWarnings($items, $opts)];
    }

    /**
     * Проверки, которые имеет смысл показать до импорта: чем запись рискует
     * (не будет сопоставлена, потеряет категории, не пройдёт валидацию).
     *
     * @return array<int,string>
     */
    protected function collectWarnings(array $items, array $opts): array
    {
        $warnings = [];
        $updateBy = $opts['update_by'] ?? 'none';
        $matchBy  = $opts['match_category_by'] ?? 'title';
        $createMissing = (bool) ($opts['create_missing_cats'] ?? false);

        $seenSlugs = [];
        $noKey = 0;
        $badSlug = [];
        $noTitle = 0;
        $missingCats = [];

        foreach ($items as $i => $raw) {
            $line = $i + 1;

            // Нечем сопоставить запись — она всегда будет создаваться заново
            if ($updateBy !== 'none' && empty($raw[$updateBy])) {
                $noKey++;
            }

            if (empty($raw['title'])) {
                $noTitle++;
            }

            $slug = $raw['slug'] ?? null;

            if ($slug) {
                if (!preg_match('/^[a-z0-9-]+$/', (string) $slug)) {
                    $badSlug[] = $slug;
                }
                if (isset($seenSlugs[$slug])) {
                    $warnings[] = "Строка {$line}: slug «{$slug}» повторяется в файле (строка {$seenSlugs[$slug]}) — записи перезапишут друг друга.";
                } else {
                    $seenSlugs[$slug] = $line;
                }
            }

            // Категории, которых нет в базе и которые не будут созданы
            if (!$createMissing && !empty($raw['categories']) && is_array($raw['categories'])) {
                foreach ($raw['categories'] as $c) {
                    $value = is_array($c) ? ($c[$matchBy] ?? null) : $c;
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $exists = Category::where($matchBy === 'id' ? 'id' : $matchBy, $value)->exists();
                    if (!$exists) {
                        $missingCats[(string) $value] = true;
                    }
                }
            }
        }

        if ($noKey > 0) {
            $warnings[] = "Записей без поля «{$updateBy}»: {$noKey} — они будут созданы заново, а не обновлены.";
        }
        if ($noTitle > 0) {
            $warnings[] = "Записей без заголовка: {$noTitle} — такие строки будут пропущены при импорте.";
        }
        if ($badSlug) {
            $sample = implode(', ', array_slice($badSlug, 0, 5));
            $warnings[] = 'Недопустимые slug (разрешены строчные латинские буквы, цифры и дефис): ' . $sample
                . (count($badSlug) > 5 ? ' и ещё ' . (count($badSlug) - 5) : '') . '.';
        }
        if ($missingCats) {
            $names = array_slice(array_keys($missingCats), 0, 5);
            $warnings[] = 'Категории не найдены по «' . $matchBy . '»: ' . implode(', ', $names)
                . (count($missingCats) > 5 ? ' и ещё ' . (count($missingCats) - 5) : '')
                . '. Включите «Создавать новые категории», иначе связи потеряются.';
        }

        return $warnings;
    }

    public function import(array $opts): array
    {
        [$items, $media] = $this->read($opts['file']);
        $createMissing = (bool)($opts['create_missing_cats'] ?? false);
        $matchBy       = $opts['match_category_by'];
        $updateBy      = $opts['update_by']; // id|slug|none

        if (!empty($opts['dry_run'])) {
            return $this->summarize($items, $opts);
        }

        $created = $updated = $skipped = 0;
        $errors = [];

        Log::info('NewsIO: Начало импорта', [
            'total_items' => count($items),
            'update_by' => $updateBy,
            'match_by' => $matchBy,
        ]);

        DB::transaction(function () use ($items, $media, $createMissing, $matchBy, $updateBy, &$created, &$updated, &$skipped, &$errors) {
            foreach ($items as $index => $raw) {
                try {
                    $payload = $this->normalize($raw);
                    
                    // Валидация обязательных полей
                    $validation = $this->validateItem($payload);
                    if (!$validation['valid']) {
                        $errors[] = "Строка " . ($index + 1) . ": " . $validation['error'];
                        $skipped++;
                        continue;
                    }

                    // поймать существующую запись
                    $news = null;
                    if ($updateBy === 'id' && !empty($payload['id'])) {
                        $news = News::find($payload['id']);
                    } elseif ($updateBy === 'slug' && !empty($payload['slug'])) {
                        $news = News::where('slug', $payload['slug'])->first();
                    }

                    if (!$news && $updateBy !== 'none') {
                        // если нет — создаём
                        $news = new News();
                    } elseif (!$news && $updateBy === 'none') {
                        $news = new News(); // всегда создаём
                    }

                    if (!$news) { 
                        $skipped++; 
                        continue; 
                    }

                    // Генерация slug если отсутствует
                    if (empty($payload['slug']) && !empty($payload['title'])) {
                        $payload['slug'] = Str::slug($payload['title']) . '-' . uniqid();
                    }

                    // основные поля
                    $news->fill(Arr::only($payload, [
                        'slug','title','content','template','published','price','stock','is_promo',
                        'meta_title','meta_description','meta_keywords','meta_header'
                    ]));

                    // media из ZIP? если cover указан и такой файл есть в media — переложим в public
                    if (!empty($payload['cover']) && is_string($payload['cover'])) {
                        $base = basename($payload['cover']);
                        if (!empty($media[$base])) {
                            $publicPath = 'uploads/news/'.$base;
                            Storage::disk('public')->put($publicPath, $media[$base]);
                            $news->cover = $publicPath;
                        } else {
                            // оставим как есть (отн. путь)
                            $news->cover = $payload['cover'];
                        }
                    }

                    $news->save();

                    // категории
                    $catIds = [];
                    foreach ($payload['categories'] as $catRaw) {
                        $cat = $this->resolveCategory($catRaw, $matchBy, $createMissing);
                        if ($cat) $catIds[] = $cat->id;
                    }
                    $news->categories()->sync($catIds);

                    $news->refresh();
                    if ($news->wasRecentlyCreated) {
                        $created++;
                        Log::debug('NewsIO: Создана новость', ['id' => $news->id, 'slug' => $news->slug]);
                    } else {
                        $updated++;
                        Log::debug('NewsIO: Обновлена новость', ['id' => $news->id, 'slug' => $news->slug]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Строка " . ($index + 1) . ": " . $e->getMessage();
                    $skipped++;
                    Log::error('NewsIO: Ошибка импорта строки', [
                        'index' => $index + 1,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        });

        Log::info('NewsIO: Импорт завершен', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors_count' => count($errors)
        ]);

        return compact('created','updated','skipped','errors');
    }

    protected function read(UploadedFile $file): array
    {
        $items = [];
        $media = []; // для zip: ['filename.ext' => binary]
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'zip') {
            $tmp = $file->getPathname();
            $zip = new \ZipArchive();
            $zip->open($tmp);

            // manifest.json
            $manifest = $zip->getStream('manifest.json');
            if (!$manifest) { throw new \RuntimeException('manifest.json not found in ZIP'); }
            $json = stream_get_contents($manifest);
            fclose($manifest);

            $decoded = json_decode($json, true);
            $items = $decoded['items'] ?? $decoded;

            // media/*
            for ($i=0; $i<$zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'media/') && substr($name, -1) !== '/') {
                    $stream = $zip->getStream($name);
                    $media[basename($name)] = stream_get_contents($stream);
                    fclose($stream);
                }
            }
            $zip->close();
            return [$items, $media];
        }

        if (in_array($ext, ['json','txt'])) {
            $content = file_get_contents($file->getPathname());
            // ndjson?
            if (preg_match('/\n/', $content) && !str_starts_with(trim($content),'[')) {
                foreach (preg_split("/\r\n|\n|\r/", $content) as $line) {
                    $line = trim($line); if ($line==='') continue;
                    $items[] = json_decode($line, true);
                }
            } else {
                $decoded = json_decode($content, true);
                $items = isset($decoded['items']) ? $decoded['items'] : $decoded;
            }
            return [$items, $media];
        }

        if ($ext === 'csv') {
            $fh = fopen($file->getPathname(), 'r');
            // Пропускаем UTF-8 BOM если есть
            $bom = fread($fh, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($fh);
            }
            
            $header = fgetcsv($fh);
            while ($row = fgetcsv($fh)) {
                if (count($row) !== count($header)) {
                    continue; // Пропускаем некорректные строки
                }
                $rec = array_combine($header, $row);
                $cats = array_filter(array_map('trim', explode(',', (string)($rec['categories'] ?? ''))));
                $items[] = [
                    'id'             => $rec['id'] ?? null,
                    'slug'           => $rec['slug'] ?? null,
                    'title'          => $rec['title'] ?? null,
                    'content'        => $rec['content'] ?? null,
                    'template'       => $rec['template'] ?? 'default',
                    'published'      => (int)($rec['published'] ?? 1),
                    'cover'          => $rec['cover'] ?? null,
                    'price'          => $rec['price'] ?? null,
                    'stock'          => $rec['stock'] ?? null,
                    'is_promo'       => (int)($rec['is_promo'] ?? 0),
                    'meta_title'     => $rec['meta_title'] ?? null,
                    'meta_description' => $rec['meta_description'] ?? null,
                    'meta_keywords'  => $rec['meta_keywords'] ?? null,
                    'meta_header'    => $rec['meta_header'] ?? null,
                    'categories'     => array_map(fn($s)=>['slug'=>$s], $cats),
                ];
            }
            fclose($fh);
            return [$items, $media];
        }

        throw new \InvalidArgumentException('Unsupported file type');
    }

    protected function normalize(array $raw): array
    {
        $raw['categories'] = array_values($raw['categories'] ?? []);
        return $raw;
    }

    protected function validateItem(array $payload): array
    {
        // Обязательные поля
        if (empty($payload['title'])) {
            return ['valid' => false, 'error' => 'Отсутствует обязательное поле: title'];
        }

        // Валидация slug (если указан)
        if (!empty($payload['slug']) && !preg_match('/^[a-z0-9-]+$/', $payload['slug'])) {
            return ['valid' => false, 'error' => 'Некорректный формат slug (только латиница, цифры и дефисы)'];
        }

        // Валидация published
        if (isset($payload['published']) && !in_array($payload['published'], [0, 1, true, false], true)) {
            return ['valid' => false, 'error' => 'Поле published должно быть 0 или 1'];
        }

        return ['valid' => true];
    }

    protected function resolveCategory(array|string $raw, string $matchBy, bool $createMissing): ?Category
    {
        if (is_string($raw)) $raw = ['slug' => $raw];
        $cat = null;

        if ($matchBy === 'id' && !empty($raw['id'])) {
            $cat = Category::find($raw['id']);
        } elseif ($matchBy === 'slug' && !empty($raw['slug'])) {
            $cat = Category::where('slug', $raw['slug'])->first();
        } elseif ($matchBy === 'title' && !empty($raw['title'])) {
            $cat = Category::where('title', $raw['title'])->first();
        }

        if (!$cat && $createMissing) {
            $cat = Category::create([
                'title' => $raw['title'] ?? ($raw['slug'] ?? 'Category'),
                'slug'  => $raw['slug'] ?? \Str::slug($raw['title'] ?? \Str::random(6)),
                'type'  => 'news', // если у вас есть поле type
                'active'=> 1,
            ]);
        }

        return $cat;
    }

    protected function summarize(array $items, array $opts): array
    {
        $slugs = 0;
        $ids   = 0;
        $cats  = 0;

        $catsById    = 0;
        $catsBySlug  = 0;
        $catsByTitle = 0;

        foreach ($items as $i) {
            if (!empty($i['slug'])) $slugs++;
            if (!empty($i['id'])) $ids++;

            foreach ($i['categories'] ?? [] as $c) {
                $cats++;
                if (is_array($c)) {
                    if (!empty($c['id'])) {
                        $catsById++;
                    } elseif (!empty($c['slug'])) {
                        $catsBySlug++;
                    } elseif (!empty($c['title'])) {
                        $catsByTitle++;
                    }
                } elseif (is_string($c)) {
                    // если категория пришла строкой — считаем как slug
                    $catsBySlug++;
                }
            }
        }

        return [
            'total'         => count($items),
            'with_slug'     => $slugs,
            'with_id'       => $ids,
            'cats_refs'     => $cats,
            'cats_by_id'    => $catsById,
            'cats_by_slug'  => $catsBySlug,
            'cats_by_title' => $catsByTitle,
            'update_by'     => $opts['update_by'] ?? 'none',
            'match_by'      => $opts['match_category_by'] ?? 'slug',
        ];
    }
}
