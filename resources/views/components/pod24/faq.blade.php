@props(['items'])

<section class="py-20 bg-white">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 mx-auto max-w-[60ch] text-center">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Frequently asked</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep">Still weighing it up?</h2>
        </div>

        <div class="max-w-[820px] mx-auto">
            @if ($items->isEmpty())
                <p class="text-pod-ink/60 text-sm text-center">Coming soon.</p>
            @else
                @foreach ($items as $item)
                    <details class="py-5 border-b border-pod-border @if($loop->first) open @endif" @if($loop->first) open @endif>
                        <summary class="flex justify-between items-center cursor-pointer font-semibold text-base text-pod-ink-deep list-none [&::-webkit-details-marker]:hidden">
                            <span>{{ $item->getTranslation('question', 'en') }}</span>
                            <span class="text-xl text-pod-accent font-bold">+</span>
                        </summary>
                        <div class="mt-3 text-pod-ink/70 text-sm leading-relaxed">{{ $item->getTranslation('answer', 'en') }}</div>
                    </details>
                @endforeach
            @endif
        </div>
    </div>
</section>
