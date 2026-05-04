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

    @if ($step === 5)
        <h2 class="text-xl font-semibold mb-4">Add-ons (optional)</h2>
        <div class="space-y-2">
            @foreach ($this->addons as $addon)
                <label class="flex items-center gap-3 p-3 border rounded">
                    <input type="checkbox"
                           wire:click="toggleAddon({{ $addon->id }})"
                           @checked(collect($selectedAddons)->contains('addon_id', $addon->id))>
                    <span class="flex-1">{{ $addon->getTranslation('name', 'en') }}</span>
                    <span class="font-bold">AED {{ number_format($addon->price_aed_cents / 100, 0) }}</span>
                </label>
            @endforeach
        </div>
        <button wire:click="$set('step', 6)" class="mt-4 bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
            Continue to contact details
        </button>
    @endif

    @if ($step === 6)
        <h2 class="text-xl font-semibold mb-4">Your details</h2>
        <input type="text" wire:model="contactName" placeholder="Full name" class="w-full border p-2 rounded mb-2">
        <input type="email" wire:model="contactEmail" placeholder="Email" class="w-full border p-2 rounded mb-2">
        <input type="tel" wire:model="contactPhone" placeholder="Phone (optional)" class="w-full border p-2 rounded mb-4">
        <label class="flex items-center gap-2 mb-4">
            <input type="checkbox" wire:model="marketingConsent">
            <span class="text-sm">Send me Pod24 updates and offers (you can unsubscribe anytime).</span>
        </label>
        <button wire:click="submitContact" class="bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
            Continue to payment
        </button>
    @endif

    @if ($step === 7)
        <h2 class="text-xl font-semibold mb-4">Payment</h2>
        @if ($clientSecret)
            <div id="stripe-payment-element" data-client-secret="{{ $clientSecret }}" data-booking-ulid="{{ $bookingUlid }}"></div>
            <script src="https://js.stripe.com/v3/"></script>
            <script>
                const stripe = Stripe('{{ config('stripe.key') }}');
                const elements = stripe.elements({ clientSecret: '{{ $clientSecret }}' });
                const paymentElement = elements.create('payment');
                paymentElement.mount('#stripe-payment-element');
                // ... in a real flow, attach a submit handler that calls stripe.confirmPayment
                // and on success redirects to /book/confirmed?ulid={{ $bookingUlid }}
            </script>
        @endif
    @endif
</div>
