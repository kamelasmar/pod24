<?php

namespace App\Filament\Widgets;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Catalog\Models\ServiceTier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active facilities', Facility::where('is_active', true)->count()),
            Stat::make('Service tiers', ServiceTier::count()),
            Stat::make('Hour packs (active)', HourPack::where('is_active', true)->count()),
        ];
    }
}
