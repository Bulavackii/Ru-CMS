<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileCategory extends Model
{
    protected $table = 'file_categories';  // Является ли это именем вашей таблицы?

    // Если есть какие-то связи, например, с моделью File:
    public function files()
    {
        return $this->hasMany(File::class, 'category_id'); // Убедитесь, что правильно указаны поля
    }
}
