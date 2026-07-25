<?php

namespace Modules\NewsIO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'file'                => 'required|file|mimes:json,csv,txt,zip',
            'update_by'           => 'required|in:id,slug,none',
            'create_missing_cats' => 'boolean',
            // slug тоже допустим: он есть в селекте формы и поддержан импортёром
            // (Importer::resolveCategory), а CSV вообще передаёт категории только
            // как slug. Раньше правило in:id,title отбивало такой импорт с 422.
            'match_category_by'   => 'required|in:id,slug,title',
            'dry_run'             => 'boolean',
        ];
    }
}
