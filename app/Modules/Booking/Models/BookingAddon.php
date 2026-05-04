<?php

namespace App\Modules\Booking\Models;

use App\Modules\Catalog\Models\Addon;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAddon extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['booking_id', 'addon_id', 'qty', 'price_at_booking_aed_cents'];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price_at_booking_aed_cents' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }
}
