<?php

namespace App\Modules\Catalog\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class HourPack extends Model
{
    use HasFactory, HasModuleFactory, HasTranslations {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['facility_id', 'name', 'description', 'hours', 'price_aed_cents', 'expiry_days', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'price_aed_cents' => 'integer',
            'expiry_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
