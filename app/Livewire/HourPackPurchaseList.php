<?php

namespace App\Livewire;

use App\Modules\Catalog\Models\HourPack;
use App\Modules\Customers\Actions\PurchaseHourPack;
use Livewire\Component;

class HourPackPurchaseList extends Component
{
    public function buy(int $packId)
    {
        $pack = HourPack::findOrFail($packId);
        $session = app(PurchaseHourPack::class)->execute(auth()->user(), $pack);
        return $this->redirect($session['url']);
    }

    public function render()
    {
        return view('livewire.hour-pack-purchase-list', [
            'packs' => HourPack::where('is_active', true)->get(),
        ])->extends('pod24.layouts.public');
    }
}
