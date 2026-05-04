<div class="container mx-auto max-w-2xl py-16 px-4">
    @php
        $stepLabels = ['Facility', 'Service tier', 'Date & time', 'Add-ons', 'Your details', 'Payment'];
    @endphp

    <x-pod24.wizard-progress :step="$step" :total="6" :labels="$stepLabels" />

    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-2">
        {{ $stepLabels[$step - 1] }}
    </h1>
    <p class="text-pod-muted mb-10">{{ match ($step) {
        1 => 'One studio, one click.',
        2 => 'Pick the level of production support you need.',
        3 => 'Pick a date and time at Yas Creative Hub.',
        4 => 'Optional extras to layer on top.',
        5 => 'Where should we send the confirmation?',
        6 => 'Secure checkout via Stripe.',
        default => '',
    } }}</p>

    @if ($step === 2)
        <div class="space-y-3">
            @foreach ($this->serviceTiers as $tier)
                <button wire:click="selectTier({{ $tier->id }})"
                        type="button"
                        class="w-full text-left p-5 border-2 border-pod-border rounded-lg hover:border-pod-accent hover:bg-pod-accent-soft transition-all cursor-pointer flex items-center justify-between gap-4">
                    <div>
                        <div class="font-bold text-pod-ink-deep mb-1">{{ $tier->name }}</div>
                        <div class="text-sm text-pod-muted">
                            From AED {{ number_format($tier->base_hourly_rate_aed_cents / 100, 0) }} / hour
                        </div>
                    </div>
                    <span class="text-pod-accent font-bold">→</span>
                </button>
            @endforeach
        </div>
    @endif

    @if ($step === 3)
        <div class="space-y-6">
            <div>
                <label for="wizard-date" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Date</label>
                <input type="date"
                       id="wizard-date"
                       wire:model.live="date"
                       min="{{ now('Asia/Dubai')->addDay()->toDateString() }}"
                       class="w-full border border-pod-border rounded-lg p-3 font-medium focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
            </div>

            <div>
                <label for="wizard-package" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Package</label>
                <select id="wizard-package"
                        wire:model.live="packageType"
                        class="w-full border border-pod-border rounded-lg p-3 font-medium focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                    <option value="hourly">Hourly</option>
                    <option value="half_day">Half-day (4h)</option>
                    <option value="full_day">Full-day (8h)</option>
                    <option value="multi_day">Multi-day</option>
                </select>
            </div>

            @if ($date)
                <div>
                    <label class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Time</label>
                    @if (count($this->availableSlots) > 0)
                        <div class="grid grid-cols-4 gap-2">
                            @foreach ($this->availableSlots as $slot)
                                @php $slotTime = $slot->starts_at->format('H:i'); @endphp
                                <button wire:click="selectSlot('{{ $slot->starts_at->toDateString() }}', '{{ $slotTime }}')"
                                        type="button"
                                        class="p-3 border border-pod-border rounded-lg hover:border-pod-accent hover:bg-pod-accent-soft text-pod-ink-deep font-medium transition-all cursor-pointer">
                                    {{ $slotTime }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-pod-muted text-sm py-2">No slots available on this date. Try another day.</p>
                    @endif
                </div>
            @endif

            @error('slot')
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 text-sm">{{ $message }}</div>
            @enderror
        </div>
    @endif

    @if ($step === 4)
        <div class="space-y-3 mb-8">
            @forelse ($this->addons as $addon)
                @php $isSelected = collect($selectedAddons)->contains('addon_id', $addon->id); @endphp
                <label for="addon-{{ $addon->id }}"
                       @class([
                           'flex items-center gap-4 p-4 border-2 rounded-lg cursor-pointer transition-all',
                           'border-pod-accent bg-pod-accent-soft' => $isSelected,
                           'border-pod-border hover:border-pod-accent/50' => ! $isSelected,
                       ])>
                    <input type="checkbox"
                           id="addon-{{ $addon->id }}"
                           wire:click="toggleAddon({{ $addon->id }})"
                           @checked($isSelected)
                           class="w-5 h-5 accent-pod-accent cursor-pointer">
                    <div class="flex-1">
                        <div class="font-bold text-pod-ink-deep">{{ $addon->getTranslation('name', 'en') }}</div>
                        @if ($desc = $addon->getTranslation('description', 'en'))
                            <div class="text-sm text-pod-muted">{{ $desc }}</div>
                        @endif
                    </div>
                    <span class="font-bold text-pod-ink-deep whitespace-nowrap">
                        AED {{ number_format($addon->price_aed_cents / 100, 0) }}
                    </span>
                </label>
            @empty
                <p class="text-pod-muted text-sm">No add-ons available for this facility.</p>
            @endforelse
        </div>
        <button wire:click="$set('step', 5)"
                type="button"
                class="w-full bg-pod-ink-deep text-white py-4 rounded-full font-bold hover:bg-pod-accent hover:text-pod-ink-deep transition-all cursor-pointer">
            Continue to your details →
        </button>
    @endif

    @if ($step === 5)
        <div class="space-y-4 mb-6">
            <div>
                <label for="contact-name" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Full name</label>
                <input type="text"
                       id="contact-name"
                       wire:model="contactName"
                       autocomplete="name"
                       class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                @error('contactName')<p class="text-red-700 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="contact-email" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Email</label>
                <input type="email"
                       id="contact-email"
                       wire:model="contactEmail"
                       autocomplete="email"
                       class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                @error('contactEmail')<p class="text-red-700 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="contact-phone" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Phone <span class="text-pod-muted font-normal normal-case tracking-normal">(optional)</span></label>
                <input type="tel"
                       id="contact-phone"
                       wire:model="contactPhone"
                       autocomplete="tel"
                       class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
            </div>
        </div>
        <label class="flex items-start gap-3 mb-6 cursor-pointer">
            <input type="checkbox"
                   wire:model="marketingConsent"
                   class="mt-1 w-4 h-4 accent-pod-accent cursor-pointer">
            <span class="text-sm text-pod-muted leading-relaxed">Send me Pod24 updates and offers. You can unsubscribe anytime.</span>
        </label>
        <button wire:click="submitContact"
                wire:loading.attr="disabled"
                wire:target="submitContact"
                type="button"
                class="w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full font-bold hover:bg-pod-accent-deep hover:text-white transition-all cursor-pointer disabled:opacity-60 disabled:cursor-wait">
            <span wire:loading.remove wire:target="submitContact">Continue to payment →</span>
            <span wire:loading wire:target="submitContact">Locking your slot…</span>
        </button>
    @endif

    @if ($step === 6)
        @if ($clientSecret)
            <div class="bg-white border border-pod-border rounded-lg p-6 mb-4">
                <div id="stripe-payment-element"
                     data-client-secret="{{ $clientSecret }}"
                     data-booking-ulid="{{ $bookingUlid }}"></div>
            </div>
            <p class="text-xs text-pod-muted text-center">Secured by Stripe · capacity locked at confirmation</p>
            <script src="https://js.stripe.com/v3/"></script>
            <script>
                const stripe = Stripe('{{ config('stripe.key') }}');
                const elements = stripe.elements({ clientSecret: '{{ $clientSecret }}' });
                const paymentElement = elements.create('payment');
                paymentElement.mount('#stripe-payment-element');
            </script>
        @else
            <div class="text-pod-muted text-sm">Preparing checkout…</div>
        @endif
    @endif
</div>
