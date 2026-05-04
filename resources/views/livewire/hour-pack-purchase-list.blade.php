<section class="container mx-auto py-12 px-4 max-w-4xl">
    <h1 class="text-3xl font-bold mb-8">Pre-paid hour packs</h1>
    <div class="grid md:grid-cols-2 gap-4">
        @foreach ($packs as $pack)
            <div class="border border-pod-border rounded p-6">
                <h2 class="text-xl font-bold">{{ $pack->getTranslation('name', 'en') }}</h2>
                <p class="text-pod-muted">{{ $pack->getTranslation('description', 'en') }}</p>
                <div class="text-3xl font-bold my-4">AED {{ number_format($pack->price_aed_cents / 100, 0) }}</div>
                <button wire:click="buy({{ $pack->id }})"
                        class="bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
                    Buy now →
                </button>
            </div>
        @endforeach
    </div>
</section>
