<?php

namespace App\Modules\Availability\Models;

use App\Modules\Catalog\Models\Facility;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityBlackout extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['facility_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
