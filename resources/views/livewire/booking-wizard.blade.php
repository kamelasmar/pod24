<div class="container mx-auto max-w-3xl py-12 px-4">
    <h1 class="text-3xl font-bold mb-8">Book Pod24 — Step {{ $step }} of 7</h1>

    @if ($step === 2)
        <h2 class="text-xl font-semibold mb-4">Pick a service tier</h2>
        <div class="space-y-3">
            @foreach ($this->serviceTiers as $tier)
                <button wire:click="selectTier({{ $tier->id }})"
                        class="w-full text-left p-4 border border-pod-border rounded hover:border-pod-accent">
                    <div class="font-bold">{{ $tier->name }}</div>
                    <div class="text-sm text-pod-muted">
                        AED {{ number_format($tier->base_hourly_rate_aed_cents / 100, 0) }}/hr
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    @if ($step === 3)
        <h2 class="text-xl font-semibold mb-4">Pick a date and time</h2>
        <input type="date" wire:model.live="date" class="border p-2 rounded mb-4">
        <select wire:model.live="packageType" class="border p-2 rounded mb-4">
            <option value="hourly">Hourly</option>
            <option value="half_day">Half-day (4h)</option>
            <option value="full_day">Full-day (8h)</option>
        </select>
        @if ($this->availableSlots)
            <div class="grid grid-cols-4 gap-2">
                @foreach ($this->availableSlots as $slot)
                    <button wire:click="selectSlot('{{ $slot->starts_at->toDateString() }}', '{{ $slot->starts_at->format('H:i') }}')"
                            class="p-2 border border-pod-border rounded hover:border-pod-accent">
                        {{ $slot->starts_at->format('H:i') }}
                    </button>
                @endforeach
            </div>
        @elseif ($date)
            <p class="text-pod-muted">No slots available on this date.</p>
        @endif
    @endif

    @if ($step === 4)
        <h2 class="text-xl font-semibold mb-4">Where should we deliver?</h2>
        <input type="text" wire:model="address.city" placeholder="City"
               class="w-full border p-2 rounded mb-4">
        <button wire:click="submitAddress" class="bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
            Continue
        </button>
        <p class="text-sm text-pod-muted mt-2">Self-serve booking is for Abu Dhabi onsite only. For other UAE cities, we'll route you to a custom quote form.</p>
    @endif
</div>
