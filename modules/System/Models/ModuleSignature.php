<?php

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🧩 Модель ModuleSignature
 *
 * Хранит цифровые подписи модулей для проверки подлинности
 */
class ModuleSignature extends Model
{
    protected $fillable = [
        'module_name',
        'signature',
        'public_key',
        'signed_at',
        'hash_algorithm',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }
}
