<section class="py-20 bg-white">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Service inclusions</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">Production support, every session.</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-5">
            @php
                $items = [
                    ['title' => 'Trained operator', 'desc' => 'A Pod24 engineer runs cameras, audio, lighting, and live switching for your entire session.'],
                    ['title' => 'Raw footage on HDD', 'desc' => 'Individually tracked audio stems plus multi-cam video - delivered on an external HDD or shared digitally.'],
                    ['title' => 'Remote guest support', 'desc' => 'Dial in co-hosts or guests from anywhere - studio-grade quality preserved.'],
                    ['title' => 'Guest headphones & snacks', 'desc' => 'Comfort kit for up to four hosts. Water, coffee, Pod24 merch on request.'],
                    ['title' => 'Post-production (add-on)', 'desc' => 'Editing, highlights, subtitles, jingles, branding - quoted separately at checkout or via the corporate flow.'],
                ];
            @endphp
            @foreach ($items as $item)
                <div class="bg-white border border-pod-border rounded p-7 transition-all duration-200 hover:border-pod-accent hover:-translate-y-0.5">
                    <div class="w-11 h-11 rounded-full bg-pod-accent-soft text-pod-accent flex items-center justify-center font-bold mb-4 text-lg">⦿</div>
                    <h4 class="text-base font-bold text-pod-ink-deep mb-1">{{ $item['title'] }}</h4>
                    <p class="text-sm text-pod-ink/70 leading-relaxed">{{ $item['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
