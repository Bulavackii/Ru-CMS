<?php

/**
 * 🧩 module_path()
 *
 * Возвращает абсолютный путь к указанному модулю в директории `modules/`.
 * Работает как `base_path()` + `modules/...`, удобно для сервис-провайдеров, миграций и пр.
 *
 * 🔹 Пример использования:
 *   module_path('News') → /путь_к_проекту/modules/News
 *   module_path('News', 'Routes/web.php') → /путь_к_проекту/modules/News/Routes/web.php
 *
 * @param string $module Название модуля (папки внутри `modules/`)
 * @param string $path   Относительный путь внутри модуля
 * @return string        Абсолютный путь до файла или папки
 */
if (!function_exists('module_path')) {
    function module_path(string $module, string $path = ''): string
    {
        return base_path('modules/' . $module . ($path ? '/' . $path : ''));
    }
}
