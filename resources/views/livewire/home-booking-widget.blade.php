<div class="bg-white text-pod-ink rounded-lg p-6 md:p-8 shadow-2xl">
    {{-- Service tier chips --}}
    <div class="text-xs uppercase tracking-[0.2em] text-pod-muted font-bold mb-3">1. Service tier</div>
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ($this->serviceTiers as $tier)
            <button type="button"
                    wire:click="selectTier({{ $tier->id }})"
                    @class([
                        'px-3 py-2 rounded-full text-xs font-semibold border transition-all',
                        'bg-pod-ink-deep text-white border-pod-ink-deep' => $selectedTierId === $tier->id,
                        'border-pod-border text-pod-ink hover:border-pod-accent' => $selectedTierId !== $tier->id,
                    ])>
                {{ $tier->name }}
                <span class="ml-1 opacity-70">AED {{ number_format($tier->base_hourly_rate_aed_cents / 100, 0) }}/hr</span>
            </button>
        @endforeach
    </div>

    {{-- Hours --}}
    <div class="text-xs uppercase tracking-[0.2em] text-pod-muted font-bold mb-3">2. Hours</div>
    <div class="grid grid-cols-4 sm:grid-cols-8 gap-1.5 mb-6">
        @foreach (range(1, 8) as $h)
            <button wire:click="setDurationHours({{ $h }})"
                    type="button"
                    @class([
                        'py-2 rounded text-xs font-bold transition-all',
                        'bg-pod-ink-deep text-white' => $durationHours === $h,
                        'border border-pod-border hover:border-pod-accent text-pod-ink' => $durationHours !== $h,
                    ])>{{ $h }}h</button>
        @endforeach
    </div>

    {{-- Calendar --}}
    <div class="text-xs uppercase tracking-[0.2em] text-pod-muted font-bold mb-3">3. Date</div>
    <div class="flex justify-between items-center mb-3">
        <button wire:click="prevMonth" type="button"
                class="w-8 h-8 rounded-full border border-pod-border hover:border-pod-accent hover:text-pod-accent flex items-center justify-center">‹</button>
        <strong class="text-pod-ink-deep text-base">{{ $this->monthLabel }}</strong>
        <button wire:click="nextMonth" type="button"
                class="w-8 h-8 rounded-full border border-pod-border hover:border-pod-accent hover:text-pod-accent flex items-center justify-center">›</button>
    </div>

    <div class="grid grid-cols-7 gap-1 text-xs mb-1">
        @foreach (['M','T','W','T','F','S','S'] as $dow)
            <div class="text-center py-1 text-pod-muted font-semibold tracking-wide">{{ $dow }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1 text-sm mb-6">
        @foreach ($this->monthGrid as $cell)
            @if (! $cell['inMonth'] || $cell['isPast'])
                <div class="aspect-square flex items-center justify-center text-pod-ink/25 cursor-default">{{ $cell['day'] }}</div>
            @else
                <button wire:click="selectDate('{{ $cell['date'] }}')"
                        type="button"
                        @class([
                            'aspect-square flex items-center justify-center rounded-full font-medium transition-all',
                            'bg-pod-accent text-pod-ink-deep font-bold' => $selectedDate === $cell['date'],
                            'hover:bg-pod-accent-soft hover:text-pod-accent-deep' => $selectedDate !== $cell['date'],
                            'ring-2 ring-pod-accent ring-inset' => $cell['isToday'] && $selectedDate !== $cell['date'],
                        ])>
                    {{ $cell['day'] }}
                </button>
            @endif
        @endforeach
    </div>

    @if ($selectedDate)
        <div class="text-xs uppercase tracking-[0.2em] text-pod-muted font-bold mb-3">4. Time</div>
        @if (count($this->availableSlots) === 0)
            <p class="text-sm text-pod-muted py-4">No slots available on this date. Try another day.</p>
        @else
            <div class="grid grid-cols-4 gap-2 mb-6">
                @foreach ($this->availableSlots as $slot)
                    @php $time = $slot->starts_at->format('H:i'); @endphp
                    <button wire:click="selectTime('{{ $time }}')"
                            type="button"
                            @class([
                                'py-2 text-center text-sm rounded border font-medium transition-all',
                                'bg-pod-accent text-pod-ink-deep border-pod-accent font-bold' => $selectedTime === $time,
                                'border-pod-border hover:border-pod-accent' => $selectedTime !== $time,
                            ])>
                        {{ $time }}
                    </button>
                @endforeach
            </div>
        @endif
    @endif

    @if ($selectedDate && $selectedTime && $selectedTierId)
        @php $tier = $this->selectedTier; @endphp
        <div class="bg-pod-surface rounded p-4 mb-4 text-sm">
            <div class="flex justify-between items-baseline mb-1">
                <span class="text-pod-muted">Tier</span>
                <span class="font-bold">{{ $tier?->name }}</span>
            </div>
            <div class="flex justify-between items-baseline mb-1">
                <span class="text-pod-muted">Date & time</span>
                <span class="font-bold">{{ \Carbon\Carbon::parse($selectedDate)->format('D, M j') }} · {{ $selectedTime }}</span>
            </div>
            <div class="flex justify-between items-baseline">
                <span class="text-pod-muted">Length</span>
                <span class="font-bold">{{ $durationHours }} hour{{ $durationHours > 1 ? 's' : '' }}</span>
            </div>
        </div>
        <button wire:click="continueToCheckout"
                type="button"
                class="block w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full text-center font-bold text-base hover:bg-pod-accent-deep hover:text-white transition-all">
            Continue to checkout →
        </button>
    @else
        <button disabled
                class="block w-full bg-pod-border text-pod-ink/40 py-4 rounded-full text-center font-bold text-base cursor-not-allowed">
            Pick a date and time
        </button>
    @endif

    <div class="text-xs text-pod-muted text-center mt-3">Secure checkout via Stripe · capacity locked at confirmation</div>
</div>
