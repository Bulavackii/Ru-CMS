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
            'match_category_by'   => 'required|in:id,title',
            'dry_run'             => 'boolean',
        ];
    }
}
