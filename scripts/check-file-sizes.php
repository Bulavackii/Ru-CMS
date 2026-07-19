<?php

echo "Проверка размеров файлов:\n\n";

$files = [
    ['public/assets/js/chart.min.js', 'Chart.js', 1000],
    ['public/assets/js/sortable.min.js', 'SortableJS', 1000],
    ['public/assets/icons/tabler-icons.woff2', 'Tabler Icons Font', 1000],
    ['public/assets/js/prism-html.min.js', 'Prism HTML', 100],
    ['public/assets/css/tabler-icons.min.css', 'Tabler Icons CSS', 100],
];

foreach ($files as $file) {
    list($path, $name, $minSize) = $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $status = $size < $minSize ? '⚠️  МИНИМАЛЬНАЯ ВЕРСИЯ/ЗАГЛУШКА' : '✅ OK';
        echo sprintf("%-25s: %8s KB - %s\n", $name, number_format($size / 1024, 2), $status);
    } else {
        echo sprintf("%-25s: ОТСУТСТВУЕТ\n", $name);
    }
}





