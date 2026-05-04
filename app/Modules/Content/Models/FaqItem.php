<?php

namespace App\Modules\Content\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class FaqItem extends Model
{
    use HasFactory, HasModuleFactory, HasTranslations {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $table = 'faq_items';

    protected $fillable = ['question', 'answer', 'is_published', 'sort_order'];

    public array $translatable = ['question', 'answer'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
