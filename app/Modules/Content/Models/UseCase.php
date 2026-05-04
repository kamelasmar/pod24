<?php

namespace App\Modules\Content\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class UseCase extends Model
{
    use HasFactory, HasModuleFactory, HasTranslations {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $table = 'use_cases';

    protected $fillable = ['title', 'description', 'image_path', 'is_published', 'sort_order'];

    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
