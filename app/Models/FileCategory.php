<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileCategory extends Model
{
    // Указываем поля, которые могут быть массово присвоены
    protected $fillable = ['name', 'icon'];
}
