<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ServiceTier extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['facility_id', 'name', 'description', 'base_hourly_rate_aed_cents', 'sort_order', 'is_active'];

    public array $translatable = ['description'];

    protected function casts(): array
    {
        return [
            'base_hourly_rate_aed_cents' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ServiceTierFactory::new();
    }
}
