<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 🔍 SearchService - Сервис для полнотекстового поиска
 * 
 * Поддерживает:
 * - Elasticsearch (если настроен)
 * - Fallback на обычный поиск через LIKE
 */
class SearchService
{
    private ?string $elasticsearchHost = null;
    private bool $elasticsearchEnabled = false;

    public function __construct()
    {
        $this->elasticsearchHost = config('services.elasticsearch.host');
        $this->elasticsearchEnabled = !empty($this->elasticsearchHost) && config('services.elasticsearch.enabled', false);
    }

    /**
     * 🔍 Поиск по контенту
     */
    public function search(string $query, string $modelType = null, int $limit = 20): array
    {
        if ($this->elasticsearchEnabled) {
            return $this->searchWithElasticsearch($query, $modelType, $limit);
        }

        return $this->searchWithDatabase($query, $modelType, $limit);
    }

    /**
     * 🔍 Поиск через Elasticsearch
     */
    private function searchWithElasticsearch(string $query, ?string $modelType, int $limit): array
    {
        try {
            $index = config('services.elasticsearch.index', 'cms_content');
            
            $body = [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['title^3', 'content', 'meta_description'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO',
                    ],
                ],
                'size' => $limit,
            ];

            if ($modelType) {
                $body['query']['multi_match']['filter'] = [
                    'term' => ['model_type' => $modelType],
                ];
            }

            $response = Http::post("{$this->elasticsearchHost}/{$index}/_search", $body);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatElasticsearchResults($data);
            }
        } catch (\Exception $e) {
            Log::warning('Elasticsearch search failed, falling back to database', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->searchWithDatabase($query, $modelType, $limit);
    }

    /**
     * 🔍 Поиск через базу данных (fallback)
     */
    private function searchWithDatabase(string $query, ?string $modelType, int $limit): array
    {
        $results = [];

        // Поиск в новостях
        if (!$modelType || $modelType === 'Modules\\News\\Models\\News') {
            $news = \Modules\News\Models\News::where('title', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%")
                ->limit($limit)
                ->get();

            foreach ($news as $item) {
                $results[] = [
                    'id' => $item->id,
                    'type' => 'news',
                    'title' => $item->title,
                    'content' => \Illuminate\Support\Str::limit(strip_tags($item->content), 200),
                    'url' => route('news.show', $item->slug),
                ];
            }
        }

        // Поиск в пользователях
        if (!$modelType || $modelType === 'App\\Models\\User') {
            $users = \App\Models\User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit($limit)
                ->get();

            foreach ($users as $user) {
                $results[] = [
                    'id' => $user->id,
                    'type' => 'user',
                    'title' => $user->name,
                    'content' => $user->email,
                    'url' => route('admin.users.index'),
                ];
            }
        }

        return $results;
    }

    /**
     * 📝 Индексировать документ в Elasticsearch
     */
    public function indexDocument($model): bool
    {
        if (!$this->elasticsearchEnabled) {
            return false;
        }

        try {
            $index = config('services.elasticsearch.index', 'cms_content');
            $id = get_class($model) . '_' . $model->id;

            $document = [
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'title' => $model->title ?? $model->name ?? '',
                'content' => strip_tags($model->content ?? $model->description ?? ''),
                'meta_description' => $model->meta_description ?? '',
                'created_at' => $model->created_at?->toIso8601String(),
                'updated_at' => $model->updated_at?->toIso8601String(),
            ];

            Http::put("{$this->elasticsearchHost}/{$index}/_doc/{$id}", $document);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to index document in Elasticsearch', [
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 🗑️ Удалить документ из индекса
     */
    public function deleteDocument($model): bool
    {
        if (!$this->elasticsearchEnabled) {
            return false;
        }

        try {
            $index = config('services.elasticsearch.index', 'cms_content');
            $id = get_class($model) . '_' . $model->id;

            Http::delete("{$this->elasticsearchHost}/{$index}/_doc/{$id}");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete document from Elasticsearch', [
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 🔄 Форматировать результаты Elasticsearch
     */
    private function formatElasticsearchResults(array $data): array
    {
        $results = [];

        foreach ($data['hits']['hits'] ?? [] as $hit) {
            $source = $hit['_source'];
            $results[] = [
                'id' => $source['model_id'],
                'type' => strtolower(class_basename($source['model_type'])),
                'title' => $source['title'],
                'content' => \Illuminate\Support\Str::limit($source['content'], 200),
                'score' => $hit['_score'],
            ];
        }

        return $results;
    }
}

