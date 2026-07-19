<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Fragment extends Model
{
    protected $table = 'visual_fragments';
    protected $fillable = ['slug','title','type','zone','schema','data','html_cached','css_inline','is_active','updated_by'];
    
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 👤 Связь с пользователем, обновившим фрагмент
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 📜 Связь с ревизиями фрагмента
     */
    public function revisions(): HasMany
    {
        return $this->morphMany(Revision::class, 'target');
    }
}
