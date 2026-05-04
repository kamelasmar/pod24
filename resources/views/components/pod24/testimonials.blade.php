@props(['items'])

<section class="py-20 bg-white">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Creators who've recorded with us</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">What hosts say after the first session.</h2>
        </div>

        @if ($items->isEmpty())
            <p class="text-pod-ink/60 text-sm">Coming soon.</p>
        @else
            <div class="grid md:grid-cols-3 gap-6 mt-12">
                @foreach ($items as $item)
                    <div class="bg-white border border-pod-border rounded p-7 relative transition-all duration-200 hover:border-pod-accent">
                        <span class="absolute top-3 right-5 text-5xl font-bold leading-none text-pod-accent opacity-30" style="font-family:Georgia,serif;">"</span>
                        <p class="text-base leading-relaxed mb-5 text-pod-ink relative">{{ $item->getTranslation('quote', 'en') }}</p>
                        <div class="flex gap-3 items-center">
                            <div class="w-10 h-10 rounded-full bg-pod-border-soft shrink-0"></div>
                            <div>
                                <div class="text-sm font-bold text-pod-ink-deep">{{ $item->name }}</div>
                                <div class="text-xs text-pod-muted">{{ $item->role }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
