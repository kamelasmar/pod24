<?php

namespace App\Modules\Catalog\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityPricing extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $table = 'facility_pricing';

    protected $fillable = ['facility_id', 'service_tier_id', 'package_type', 'hours', 'price_aed_cents'];

    public const PACKAGE_TYPES = ['hourly', 'multi_day'];

    /** Min/max consecutive hours bookable on a single day for an hourly session. */
    public const HOURLY_MIN = 1;
    public const HOURLY_MAX = 8;

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'price_aed_cents' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function serviceTier(): BelongsTo
    {
        return $this->belongsTo(ServiceTier::class);
    }
}
