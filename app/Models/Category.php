<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['title', 'type', 'icon'];

    public function files()
    {
        return $this->hasMany(File::class, 'category_id');
    }
}
