<div class="bg-pod-ink-deep text-white min-h-screen -mt-16 pt-24 pb-16 relative overflow-hidden">
    <div class="absolute -top-[200px] right-[-200px] w-[600px] h-[600px] pointer-events-none" style="background:radial-gradient(circle,rgba(0,185,227,0.10) 0%,transparent 60%);"></div>

    <div class="container mx-auto max-w-2xl px-4 relative">
        @php
            $stepLabels = ['Service tier', 'Date & time', 'Add-ons', 'Your details', 'Payment'];
        @endphp

        <x-pod24.wizard-progress :step="$step" :total="5" :labels="$stepLabels" theme="dark" />

        <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-2 text-white">
            {{ $stepLabels[$step - 1] ?? '' }}
        </h1>
        <p class="text-white/60 mb-10">{{ match ($step) {
            1 => 'Pick the level of production support you need.',
            2 => 'Pick a date and time at Yas Creative Hub.',
            3 => 'Optional extras to layer on top.',
            4 => 'Where should we send the confirmation?',
            5 => 'Secure checkout via Stripe.',
            default => '',
        } }}</p>

        @if ($step === 1)
            <div class="space-y-3">
                @foreach ($this->serviceTiers as $tier)
                    <button wire:click="selectTier({{ $tier->id }})"
                            type="button"
                            class="w-full text-left p-5 bg-white/5 border-2 border-white/10 rounded-lg hover:border-pod-accent hover:bg-pod-accent/5 transition-all cursor-pointer flex items-center justify-between gap-4">
                        <div>
                            <div class="font-bold text-white mb-1">{{ $tier->name }}</div>
                            <div class="text-sm text-white/60">
                                AED {{ number_format($tier->base_hourly_rate_aed_cents / 100, 0) }} / hour
                            </div>
                        </div>
                        <span class="text-pod-accent font-bold">→</span>
                    </button>
                @endforeach
            </div>
        @endif

        @if ($step === 2)
            <div class="space-y-6">
                <div>
                    <label for="wizard-date" class="block text-xs uppercase tracking-[0.15em] text-white/50 font-bold mb-2">Date</label>
                    <input type="date"
                           id="wizard-date"
                           wire:model.live="date"
                           min="{{ now('Asia/Dubai')->addDay()->toDateString() }}"
                           class="w-full bg-white/5 border border-white/15 rounded-lg p-3 text-white font-medium focus:outline-none focus:border-pod-accent placeholder:text-white/30"
                           style="color-scheme:dark;">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-[0.15em] text-white/50 font-bold mb-2">How many hours?</label>
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                        @foreach (range(1, 8) as $h)
                            <button wire:click="setDurationHours({{ $h }})"
                                    type="button"
                                    @class([
                                        'py-3 rounded-lg font-bold text-sm transition-all cursor-pointer',
                                        'bg-pod-accent text-pod-ink-deep' => $durationHours === $h,
                                        'bg-white/5 border border-white/10 hover:border-pod-accent text-white' => $durationHours !== $h,
                                    ])>{{ $h }}h</button>
                        @endforeach
                    </div>
                    <p class="text-xs text-white/50 mt-2">Minimum 1 hour, maximum 8 hours per session. For multi-day shoots, contact us via corporate services.</p>
                </div>

                @if ($date)
                    <div>
                        <label class="block text-xs uppercase tracking-[0.15em] text-white/50 font-bold mb-2">Time</label>
                        @if (count($this->availableSlots) > 0)
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ($this->availableSlots as $slot)
                                    @php $slotTime = $slot->starts_at->format('H:i'); @endphp
                                    <button wire:click="selectSlot('{{ $slot->starts_at->toDateString() }}', '{{ $slotTime }}')"
                                            type="button"
                                            class="p-3 bg-white/5 border border-white/10 rounded-lg hover:border-pod-accent hover:bg-pod-accent/10 text-white font-medium transition-all cursor-pointer">
                                        {{ $slotTime }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="text-white/50 text-sm py-2">No slots available on this date. Try another day.</p>
                        @endif
                    </div>
                @endif

                @error('slot')
                    <div class="bg-red-500/10 border border-red-400/30 text-red-300 rounded-lg p-3 text-sm">{{ $message }}</div>
                @enderror
            </div>
        @endif

        @if ($step === 3)
            <div class="space-y-3 mb-8">
                @forelse ($this->addons as $addon)
                    @php $isSelected = collect($selectedAddons)->contains('addon_id', $addon->id); @endphp
                    <label for="addon-{{ $addon->id }}"
                           @class([
                               'flex items-center gap-4 p-4 border-2 rounded-lg cursor-pointer transition-all',
                               'border-pod-accent bg-pod-accent/10' => $isSelected,
                               'bg-white/5 border-white/10 hover:border-pod-accent/50' => ! $isSelected,
                           ])>
                        <input type="checkbox"
                               id="addon-{{ $addon->id }}"
                               wire:click="toggleAddon({{ $addon->id }})"
                               @checked($isSelected)
                               class="w-5 h-5 accent-pod-accent cursor-pointer">
                        <div class="flex-1">
                            <div class="font-bold text-white">{{ $addon->getTranslation('name', 'en') }}</div>
                            @if ($desc = $addon->getTranslation('description', 'en'))
                                <div class="text-sm text-white/60">{{ $desc }}</div>
                            @endif
                        </div>
                        <span class="font-bold text-pod-accent whitespace-nowrap">
                            AED {{ number_format($addon->price_aed_cents / 100, 0) }}
                        </span>
                    </label>
                @empty
                    <p class="text-white/50 text-sm">No add-ons available for this facility.</p>
                @endforelse
            </div>
            <button wire:click="$set('step', 4)"
                    type="button"
                    class="w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full font-bold hover:bg-white transition-all cursor-pointer">
                Continue to your details →
            </button>
        @endif

        @if ($step === 4)
            <div class="space-y-4 mb-6">
                @foreach ([
                    'contact-name' => ['Full name', 'contactName', 'name', 'text'],
                    'contact-email' => ['Email', 'contactEmail', 'email', 'email'],
                    'contact-phone' => ['Phone (optional)', 'contactPhone', 'tel', 'tel'],
                ] as $id => [$label, $model, $autocomplete, $type])
                    <div>
                        <label for="{{ $id }}" class="block text-xs uppercase tracking-[0.15em] text-white/50 font-bold mb-2">{{ $label }}</label>
                        <input type="{{ $type }}"
                               id="{{ $id }}"
                               wire:model="{{ $model }}"
                               autocomplete="{{ $autocomplete }}"
                               class="w-full bg-white/5 border border-white/15 rounded-lg p-3 text-white placeholder:text-white/30 focus:outline-none focus:border-pod-accent">
                        @error($model)<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <label class="flex items-start gap-3 mb-6 cursor-pointer">
                <input type="checkbox"
                       wire:model="marketingConsent"
                       class="mt-1 w-4 h-4 accent-pod-accent cursor-pointer">
                <span class="text-sm text-white/60 leading-relaxed">Send me Pod24 updates and offers. You can unsubscribe anytime.</span>
            </label>
            <button wire:click="submitContact"
                    wire:loading.attr="disabled"
                    wire:target="submitContact"
                    type="button"
                    class="w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full font-bold hover:bg-white transition-all cursor-pointer disabled:opacity-60 disabled:cursor-wait">
                <span wire:loading.remove wire:target="submitContact">Continue to payment →</span>
                <span wire:loading wire:target="submitContact">Locking your slot…</span>
            </button>
        @endif

        @if ($step === 5)
            @if ($clientSecret)
                <div class="bg-white border border-pod-border rounded-lg p-6 mb-4">
                    <div id="stripe-payment-element"
                         data-client-secret="{{ $clientSecret }}"
                         data-booking-ulid="{{ $bookingUlid }}"></div>
                </div>
                <p class="text-xs text-white/50 text-center">Secured by Stripe · capacity locked at confirmation</p>
                <script src="https://js.stripe.com/v3/"></script>
                <script>
                    const stripe = Stripe('{{ config('stripe.key') }}');
                    const elements = stripe.elements({ clientSecret: '{{ $clientSecret }}', appearance: { theme: 'night' } });
                    const paymentElement = elements.create('payment');
                    paymentElement.mount('#stripe-payment-element');
                </script>
            @else
                <div class="text-white/50 text-sm">Preparing checkout…</div>
            @endif
        @endif
    </div>
</div>
