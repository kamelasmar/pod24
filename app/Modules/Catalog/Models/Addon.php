<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Addon extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['facility_id', 'name', 'description', 'price_aed_cents', 'is_active', 'sort_order'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'price_aed_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\AddonFactory::new();
    }
}
