<?php

namespace Modules\NewsIO\Console;

use Illuminate\Console\Command;
use Modules\NewsIO\Services\Importer;

class ImportNews extends Command
{
    protected $signature = 'news:import
        {file : path to file in storage/app or absolute}
        {--update-by=slug : id|slug|none}
        {--match-by=slug : slug|title|id}
        {--create-missing : create categories}
        {--dry-run : do not write}';

    protected $description = 'Import News with categories';

    public function handle(Importer $importer)
    {
        $path = $this->argument('file');
        $file = new \Illuminate\Http\File(
            $path[0] === '/' ? $path : storage_path('app/'.$path)
        );

        $opts = [
            'file'                => new \Illuminate\Http\UploadedFile(
                $file->getPathname(),
                basename($file),
                null,
                null,
                true
            ),
            'update_by'           => $this->option('update-by'),
            'match_category_by'   => $this->option('match-by'),
            'create_missing_cats' => (bool)$this->option('create-missing'),
            'dry_run'             => (bool)$this->option('dry-run'),
        ];

        if ($opts['dry_run']) {
            [$summary, ] = $importer->dryRun($opts);

            $this->info('Dry run summary:');
            $this->table(
                ['Metric', 'Value'],
                collect($summary)->map(fn($v, $k) => [$k, $v])->toArray()
            );

            return 0;
        }

        $res = $importer->import($opts);

        $this->info('Import finished:');
        $this->table(
            ['Created', 'Updated', 'Skipped'],
            [[$res['created'], $res['updated'], $res['skipped']]]
        );

        return 0;
    }
}
