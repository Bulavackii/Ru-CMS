<?php

namespace Modules\NewsIO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'format'         => 'required|in:json,ndjson,csv,zip',
            'category_ids'   => 'array',
            'category_ids.*' => 'integer',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'published'      => 'nullable|in:all,1,0',
            'with_media'     => 'boolean',
            'chunk'          => 'nullable|integer|min:100|max:5000',
        ];
    }
}
