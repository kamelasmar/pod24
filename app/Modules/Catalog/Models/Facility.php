<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Facility extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['slug', 'name', 'description', 'address', 'is_active', 'sort_order'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\FacilityFactory::new();
    }

    public function serviceTiers(): HasMany
    {
        return $this->hasMany(ServiceTier::class);
    }
}
