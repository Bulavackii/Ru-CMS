<?php

return [

    // Базовые URL/поведение хоста
    'base_url'           => env('SEO_BASE_URL', ''),                 // опционально; если пусто — возьмём APP_URL/request
    'force_current_host' => (bool) env('SEO_FORCE_CURRENT_HOST', false), // переписывать чужой хост на текущий

    // Глобальные фичи модуля
    'features' => [
        'indexnow'               => env('SEO_INDEXNOW_ENABLED', false),
        'webmaster'              => env('SEO_WEBMASTER_ENABLED', false),
        'metrica'                => env('SEO_METRICA_ENABLED', false),
        'yandex_cloud'           => env('SEO_YC_ENABLED', false),
        'news_sitemap'           => env('SEO_NEWS_SITEMAP_ENABLED', false),
        'images_sitemap'         => env('SEO_IMAGES_SITEMAP_ENABLED', false),

        // Единый флаг пушбэка правок из SeoPage в источники (news/page)
        'push_back_to_sources'   => env('SEO_PUSH_BACK_TO_SOURCES', true),

        // Разрешить пушбэку менять H1 -> title источника (осторожно)
        'push_back_change_title' => env('SEO_PUSH_BACK_CHANGE_TITLE', false),
    ],

    // Путь/параметры вывода sitemap-файлов
    'sitemaps' => [
        'output_dir' => env('SEO_SITEMAPS_DIR', public_path('sitemaps')),
        'max_urls'   => (int) env('SEO_SITEMAPS_MAX_URLS', 50000),
    ],

    // Параметры карты для новостей (если включена)
    'news_sitemap' => [
        'max_items' => (int) env('SEO_NEWS_SITEMAP_MAX_ITEMS', 1000),
    ],

    // Параметры карты для изображений (если включена)
    'images_sitemap' => [
        'max_per_page' => (int) env('SEO_IMAGES_SITEMAP_MAX_PER_PAGE', 1000),
    ],

    // IndexNow
    'indexnow' => [
        'host'         => env('INDEXNOW_HOST', 'https://api.indexnow.org/indexnow'),
        'key'          => env('INDEXNOW_KEY'),
        'key_filename' => env('INDEXNOW_KEY_FILENAME', null),
        'timeout'      => (int) env('INDEXNOW_TIMEOUT', 5),
        'batch'        => (int) env('INDEXNOW_BATCH', 1000),
    ],

    // Яндекс.Вебмастер (v4)
    'webmaster' => [
        'oauth_token' => env('YANDEX_WEBMASTER_OAUTH_TOKEN'),
        'host_id'     => env('YANDEX_WEBMASTER_HOST_ID'),
        'base'        => env('YANDEX_WEBMASTER_API', 'https://api.webmaster.yandex.net/v4'),
        'timeout'     => (int) env('YANDEX_WEBMASTER_TIMEOUT', 10),
    ],

    // Яндекс.Метрика
    'metrica' => [
        'oauth_token' => env('YANDEX_METRICA_OAUTH_TOKEN'),
        'counter_id'  => env('YANDEX_METRICA_COUNTER_ID'),
        'base'        => env('YANDEX_METRICA_API', 'https://api-metrika.yandex.net/management/v1'),
        'stats'       => env('YANDEX_METRICA_STATS', 'https://api-metrika.yandex.net/stat/v1/data'),
        'timeout'     => (int) env('YANDEX_METRICA_TIMEOUT', 10),
    ],

    // Yandex Cloud Search API
    'yandex_cloud' => [
        'folder_id' => env('YANDEX_CLOUD_FOLDER_ID'),
        'api_key'   => env('YANDEX_CLOUD_API_KEY'),
        'endpoint'  => env('YANDEX_SEARCH_API_ENDPOINT', 'https://search.api.cloud.yandex.net/v2'),
        'timeout'   => (int) env('YANDEX_CLOUD_TIMEOUT', 10),
    ],

    // 🔄 Синхронизация между источниками и SeoPage
    'sync' => [
        'push_back_to_source' => env('SEO_PUSH_BACK_TO_SOURCE', true),

        'fields_map' => [
            'news' => [
                'title'       => env('SEO_SYNC_NEWS_TITLE_FIELD', 'seo_title'),
                'description' => env('SEO_SYNC_NEWS_DESCRIPTION_FIELD', 'seo_description'),
                'h1'          => env('SEO_SYNC_NEWS_H1_FIELD', 'title'),
                'keywords'    => env('SEO_SYNC_NEWS_KEYWORDS_FIELD', 'keywords'),
            ],
            'page' => [
                'title'       => env('SEO_SYNC_PAGE_TITLE_FIELD', 'seo_title'),
                'description' => env('SEO_SYNC_PAGE_DESCRIPTION_FIELD', 'seo_description'),
                'h1'          => env('SEO_SYNC_PAGE_H1_FIELD', 'title'),
                'keywords'    => env('SEO_SYNC_PAGE_KEYWORDS_FIELD', 'keywords'),
            ],
        ],
    ],

];
