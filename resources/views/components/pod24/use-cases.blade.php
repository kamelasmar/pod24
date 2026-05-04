@props(['items'])

<section class="py-20 bg-pod-surface">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Use cases</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">Built for the episodes you're already planning.</h2>
        </div>

        @if ($items->isEmpty())
            <p class="text-pod-ink/60 text-sm">Coming soon.</p>
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12">
                @foreach ($items as $item)
                    <div class="bg-white border border-pod-border rounded overflow-hidden flex flex-col transition-all duration-200 hover:border-pod-accent">
                        <div class="aspect-[4/3] bg-pod-border-soft"></div>
                        <div class="p-5">
                            <h4 class="mb-1 text-base font-bold text-pod-ink-deep">{{ $item->getTranslation('title', 'en') }}</h4>
                            <p class="text-sm text-pod-ink/70">{{ $item->getTranslation('description', 'en') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
