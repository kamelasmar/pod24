<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Facility extends Model implements HasMedia
{
    use HasFactory, HasModuleFactory, HasTranslations, InteractsWithMedia {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['slug', 'name', 'description', 'address', 'is_active', 'max_concurrent_per_day', 'sort_order'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'is_active' => 'boolean',
            'max_concurrent_per_day' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function serviceTiers(): HasMany
    {
        return $this->hasMany(ServiceTier::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    public function availabilityBlackouts(): HasMany
    {
        return $this->hasMany(AvailabilityBlackout::class);
    }
}
