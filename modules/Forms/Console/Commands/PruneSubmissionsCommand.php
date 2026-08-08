<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Forms\Models\FormSubmission;

/**
 * Удаление старых заявок.
 *
 * В заявках лежат персональные данные — имя, телефон, почта, иногда вложения.
 * Хранить их вечно просто потому, что никто не удалил, неправильно. Срок
 * задаётся в `config('forms.keep_days')`; ноль означает «хранить бессрочно»,
 * и тогда команда ничего не делает.
 *
 *   php artisan forms:prune
 *   php artisan forms:prune --days=90
 */
class PruneSubmissionsCommand extends Command
{
    protected $signature = 'forms:prune {--days= : Срок хранения в днях, перебивает настройку}';

    protected $description = 'Удалить заявки старше указанного срока вместе с их вложениями';

    public function handle(): int
    {
        if (! Schema::hasTable('form_submissions')) {
            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?? config('forms.keep_days', 0));

        if ($days <= 0) {
            $this->info('Срок хранения не задан — заявки не удаляются.');

            return self::SUCCESS;
        }

        $disk = Storage::disk(config('forms.upload_disk', 'local'));
        $deleted = 0;

        // Идём порциями: заявок может быть много, а вложения надо удалить
        // поштучно — одним запросом DELETE файлы с диска не уберёшь.
        FormSubmission::query()
            ->where('created_at', '<', now()->subDays($days))
            ->chunkById(200, function ($submissions) use ($disk, &$deleted) {
                foreach ($submissions as $submission) {
                    foreach ((array) $submission->data as $value) {
                        if (is_array($value) && ! empty($value['path'])) {
                            $disk->delete($value['path']);
                        }
                    }

                    $submission->delete();
                    $deleted++;
                }
            });

        $this->info('Удалено заявок: ' . $deleted . '.');

        return self::SUCCESS;
    }
}
