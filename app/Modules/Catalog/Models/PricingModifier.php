<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingModifier extends Model
{
    use HasFactory;

    protected $fillable = ['facility_id', 'type', 'percentage', 'after_hours_start', 'after_hours_end'];

    public const TYPES = ['weekend', 'after_hours'];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }

    protected function afterHoursStart(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? null : substr($value.':00:00', 0, 8),
        );
    }

    protected function afterHoursEnd(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? null : substr($value.':00:00', 0, 8),
        );
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PricingModifierFactory::new();
    }
}
