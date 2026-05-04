<?php

namespace App\Filament\Resources\Catalog\FacilityResource\Pages;

use App\Filament\Resources\Catalog\FacilityResource;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PricingMatrix extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = FacilityResource::class;

    protected static string $view = 'filament.resources.catalog.facility-resource.pages.pricing-matrix';

    public array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = Facility::with('serviceTiers')->findOrFail($record);
        $this->loadExistingPricing();
        $this->form->fill($this->data);
    }

    private function loadExistingPricing(): void
    {
        foreach ($this->record->serviceTiers as $tier) {
            foreach (FacilityPricing::PACKAGE_TYPES as $type) {
                $row = FacilityPricing::where([
                    'facility_id' => $this->record->id,
                    'service_tier_id' => $tier->id,
                    'package_type' => $type,
                ])->first();
                $key = "tier_{$tier->id}_{$type}";
                $this->data[$key] = $row?->price_aed_cents;
            }
        }
    }

    public function form(Form $form): Form
    {
        $fields = [];
        foreach ($this->record->serviceTiers as $tier) {
            foreach (FacilityPricing::PACKAGE_TYPES as $type) {
                $key = "tier_{$tier->id}_{$type}";
                $fields[] = Forms\Components\TextInput::make($key)
                    ->label("{$tier->name} — ".str_replace('_', ' ', $type).' (AED cents)')
                    ->numeric();
            }
        }

        return $form->schema($fields)->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($this->record->serviceTiers as $tier) {
            foreach (FacilityPricing::PACKAGE_TYPES as $type) {
                $key = "tier_{$tier->id}_{$type}";
                if (filled($data[$key] ?? null)) {
                    FacilityPricing::updateOrCreate(
                        [
                            'facility_id' => $this->record->id,
                            'service_tier_id' => $tier->id,
                            'package_type' => $type,
                        ],
                        [
                            'hours' => match ($type) {
                                'half_day' => 4,
                                'full_day' => 8,
                                default => 1,
                            },
                            'price_aed_cents' => (int) $data[$key],
                        ]
                    );
                }
            }
        }
        \Filament\Notifications\Notification::make()->title('Pricing saved')->success()->send();
    }
}
