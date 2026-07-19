<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Revision extends Model
{
    protected $table = 'visual_revisions';
    protected $fillable = ['target_type', 'target_id', 'snapshot', 'created_by'];
    
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    /**
     * 🔗 Полиморфная связь с целевым объектом (Fragment или Theme)
     */
    public function target(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 👤 Связь с пользователем, создавшим ревизию
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
