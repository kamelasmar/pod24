<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicy extends Model
{
    use HasFactory;

    protected $fillable = ['facility_id', 'hours_before_min', 'refund_percentage'];

    protected function casts(): array
    {
        return [
            'hours_before_min' => 'integer',
            'refund_percentage' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\CancellationPolicyFactory::new();
    }
}
