<?php

namespace App\Modules\Catalog\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingModifier extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['facility_id', 'type', 'percentage', 'after_hours_start', 'after_hours_end'];

    public const TYPES = ['weekend', 'after_hours'];

    // after_hours_start and after_hours_end are stored as Postgres TIME and read back as 'HH:MM:SS' strings.

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
