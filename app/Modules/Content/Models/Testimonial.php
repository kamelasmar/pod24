<?php

namespace App\Modules\Content\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model
{
    use HasFactory, HasModuleFactory, HasTranslations {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $table = 'testimonials';

    protected $fillable = ['quote', 'name', 'role', 'avatar_path', 'is_published'];

    public array $translatable = ['quote'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
