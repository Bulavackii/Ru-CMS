<?php

namespace Modules\NewsIO\Console;

use Illuminate\Console\Command;
use Modules\NewsIO\Services\Exporter;

class ExportNews extends Command
{
    protected $signature = 'news:export
        {--format=json : json|ndjson|csv|zip}
        {--categories= : comma-separated category IDs}
        {--from= : date from YYYY-MM-DD}
        {--to= : date to YYYY-MM-DD}
        {--published=all : all|1|0}
        {--with-media : include covers (zip)}
        {--chunk=1000}';

    protected $description = 'Export News with categories';

    public function handle(Exporter $exporter)
    {
        $opts = [
            'format'       => $this->option('format'),
            'category_ids' => $this->option('categories') ? array_map('intval', explode(',', $this->option('categories'))) : [],
            'date_from'    => $this->option('from'),
            'date_to'      => $this->option('to'),
            'published'    => $this->option('published'),
            'with_media'   => (bool)$this->option('with-media'),
            'chunk'        => (int)$this->option('chunk'),
        ];
        $path = $exporter->export($opts);
        $this->info('Exported: storage/'.$path);
        return 0;
    }
}
