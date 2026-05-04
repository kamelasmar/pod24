<?php

namespace App\Modules\Booking\Models;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = [
        'ulid', 'facility_id', 'customer_id', 'service_tier_id',
        'package_type', 'starts_at', 'ends_at', 'total_hours', 'status',
        'contact_name', 'contact_email', 'contact_phone', 'address',
        'subtotal_aed_cents', 'weekend_markup_aed_cents', 'after_hours_markup_aed_cents',
        'addons_aed_cents', 'hour_pack_credits_used', 'hour_pack_credit_value_aed_cents',
        'vat_aed_cents', 'total_aed_cents',
        'stripe_payment_intent_id', 'hold_expires_at', 'paid_at',
        'cancelled_at', 'cancelled_by', 'refund_amount_aed_cents',
        'marketing_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'address' => 'array',
            'status' => BookingStatus::class,
            'total_hours' => 'integer',
            'subtotal_aed_cents' => 'integer',
            'weekend_markup_aed_cents' => 'integer',
            'after_hours_markup_aed_cents' => 'integer',
            'addons_aed_cents' => 'integer',
            'hour_pack_credits_used' => 'integer',
            'hour_pack_credit_value_aed_cents' => 'integer',
            'vat_aed_cents' => 'integer',
            'total_aed_cents' => 'integer',
            'refund_amount_aed_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            if (empty($b->ulid)) {
                $b->ulid = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function serviceTier(): BelongsTo
    {
        return $this->belongsTo(ServiceTier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
