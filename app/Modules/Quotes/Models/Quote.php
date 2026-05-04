<?php

namespace App\Modules\Quotes\Models;

use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quote extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = [
        'ulid', 'type', 'status', 'event_type',
        'attendees_estimate', 'days_estimate', 'location_preference',
        'service_interests', 'preferred_dates',
        'contact_name', 'contact_email', 'contact_phone', 'contact_company',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'service_interests' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $q) {
            if (empty($q->ulid)) {
                $q->ulid = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
