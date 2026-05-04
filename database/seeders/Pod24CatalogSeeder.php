<?php

namespace Database\Seeders;

use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Catalog\Models\PricingModifier;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Seeder;

class Pod24CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::updateOrCreate(
            ['slug' => 'pod24-portable'],
            [
                'name' => ['en' => 'Pod24'],
                'description' => ['en' => 'Broadcast-grade podcast studio at Yas Creative Hub, Abu Dhabi. Walk in, press record, walk out with finished files.'],
                'address' => ['building' => 'Yas Creative Hub', 'city' => 'Abu Dhabi', 'country' => 'AE'],
                'is_active' => true,
                'max_concurrent_per_day' => 2,
                'sort_order' => 0,
            ]
        );

        $tiers = [
            ['name' => 'Recording Only',                              'rate' => 25400,  'sort' => 1],
            ['name' => 'Live Mix',                                    'rate' => 35400,  'sort' => 2],
            ['name' => 'Live Mix + Standard Edit',                    'rate' => 75400,  'sort' => 3],
            ['name' => 'Live Mix + Standard Edit + Live Stream',      'rate' => 105400, 'sort' => 4],
        ];

        $tierModels = [];
        foreach ($tiers as $t) {
            $tierModels[$t['name']] = ServiceTier::updateOrCreate(
                ['facility_id' => $facility->id, 'name' => $t['name']],
                ['base_hourly_rate_aed_cents' => $t['rate'], 'sort_order' => $t['sort'], 'is_active' => true]
            );
        }

        // Pricing matrix: hourly = base; half-day = 4h × base × 0.9; full-day = 8h × base × 0.85; multi-day = full-day × 0.9 per day
        foreach ($tierModels as $tierName => $tier) {
            $hourly = $tier->base_hourly_rate_aed_cents;

            $entries = [
                ['type' => 'hourly',    'hours' => 1, 'price' => $hourly],
                ['type' => 'half_day',  'hours' => 4, 'price' => (int) round($hourly * 4 * 0.9)],
                ['type' => 'full_day',  'hours' => 8, 'price' => (int) round($hourly * 8 * 0.85)],
                ['type' => 'multi_day', 'hours' => 1, 'price' => (int) round($hourly * 8 * 0.85 * 0.9)], // per-day, additional 10% off full-day
            ];

            foreach ($entries as $e) {
                FacilityPricing::updateOrCreate(
                    [
                        'facility_id' => $facility->id,
                        'service_tier_id' => $tier->id,
                        'package_type' => $e['type'],
                    ],
                    ['hours' => $e['hours'], 'price_aed_cents' => $e['price']]
                );
            }
        }

        PricingModifier::updateOrCreate(
            ['facility_id' => $facility->id, 'type' => 'weekend'],
            ['percentage' => 25]
        );
        PricingModifier::updateOrCreate(
            ['facility_id' => $facility->id, 'type' => 'after_hours'],
            ['percentage' => 25, 'after_hours_start' => '18:00', 'after_hours_end' => '09:00']
        );

        $cancellationTiers = [
            ['hours_before_min' => 168, 'refund_percentage' => 100],
            ['hours_before_min' => 72,  'refund_percentage' => 50],
            ['hours_before_min' => 0,   'refund_percentage' => 0],
        ];
        foreach ($cancellationTiers as $c) {
            CancellationPolicy::updateOrCreate(
                ['facility_id' => $facility->id, 'hours_before_min' => $c['hours_before_min']],
                ['refund_percentage' => $c['refund_percentage']]
            );
        }

        $addons = [
            ['name' => 'Episode editing',        'price' => 50000],
            ['name' => 'Social clips pack (5)',  'price' => 30000],
            ['name' => 'Cover art',              'price' => 25000],
            ['name' => 'Distribution to platforms', 'price' => 15000],
        ];
        foreach ($addons as $i => $a) {
            Addon::updateOrCreate(
                ['facility_id' => $facility->id, 'name->en' => $a['name']],
                ['name' => ['en' => $a['name']], 'price_aed_cents' => $a['price'], 'is_active' => true, 'sort_order' => $i]
            );
        }

        // Hour packs (Recording Only base rate × hours × volume discount)
        $packs = [
            ['hours' => 10, 'discount' => 0.10],
            ['hours' => 20, 'discount' => 0.20],
        ];
        $baseRate = $tierModels['Recording Only']->base_hourly_rate_aed_cents;
        foreach ($packs as $p) {
            $price = (int) round($baseRate * $p['hours'] * (1 - $p['discount']));
            HourPack::updateOrCreate(
                ['facility_id' => $facility->id, 'hours' => $p['hours']],
                [
                    'name' => ['en' => "{$p['hours']}-Hour Pack"],
                    'description' => ['en' => sprintf('%d hours pre-paid (%d%% off Recording Only rate). Valid for 12 months.', $p['hours'], (int) ($p['discount'] * 100))],
                    'price_aed_cents' => $price,
                    'expiry_days' => 365,
                    'is_active' => true,
                ]
            );
        }

        // Default availability: Mon-Sat 09:00-18:00 (Sunday closed)
        foreach ([1, 2, 3, 4, 5, 6] as $dayOfWeek) {
            AvailabilityRule::updateOrCreate(
                ['facility_id' => $facility->id, 'day_of_week' => $dayOfWeek],
                ['open_time' => '09:00', 'close_time' => '18:00']
            );
        }
    }
}
