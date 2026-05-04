<section class="container mx-auto py-16 px-4 max-w-4xl">
    <a href="{{ route('account.dashboard') }}" class="text-sm text-pod-muted hover:text-pod-ink-deep mb-3 inline-block">← Back to account</a>
    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Hour packs</div>
    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-3">Buy hours, save more.</h1>
    <p class="text-pod-muted mb-12 max-w-2xl">Pre-paid hour packs cost less per hour than booking ad-hoc. Hours redeem against any session at the Recording Only base rate; upgrade to a higher tier and you pay just the difference.</p>

    <div class="grid md:grid-cols-2 gap-4">
        @foreach ($packs as $pack)
            @php $perHour = $pack->price_aed_cents / max($pack->hours, 1) / 100; @endphp
            <div class="bg-white border border-pod-border rounded-lg p-7 flex flex-col hover:border-pod-accent transition-all">
                <h2 class="text-2xl font-bold tracking-tight text-pod-ink-deep mb-1">
                    {{ $pack->getTranslation('name', 'en') }}
                </h2>
                <p class="text-pod-muted text-sm mb-6 flex-1">{{ $pack->getTranslation('description', 'en') }}</p>
                <div class="flex items-baseline gap-3 mb-6">
                    <span class="text-4xl font-bold tracking-tight text-pod-ink-deep">AED {{ number_format($pack->price_aed_cents / 100, 0) }}</span>
                    <span class="text-sm text-pod-muted">AED {{ number_format($perHour, 0) }}/hr effective</span>
                </div>
                <button wire:click="buy({{ $pack->id }})"
                        wire:loading.attr="disabled"
                        wire:target="buy({{ $pack->id }})"
                        type="button"
                        class="w-full bg-pod-accent text-pod-ink-deep py-3 rounded-full font-bold hover:bg-pod-accent-deep hover:text-white transition-all cursor-pointer disabled:opacity-60 disabled:cursor-wait">
                    <span wire:loading.remove wire:target="buy({{ $pack->id }})">Buy {{ $pack->hours }} hours →</span>
                    <span wire:loading wire:target="buy({{ $pack->id }})">Redirecting to Stripe…</span>
                </button>
            </div>
        @endforeach
    </div>

    <div class="mt-10 text-xs text-pod-muted text-center">Secure checkout via Stripe · packs expire {{ $packs->first()?->expiry_days ?? 365 }} days after purchase</div>
</section>
