<section class="py-20 bg-white">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">What's included</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">Everything you need. Nothing you don't.</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-5">
            @php
                $items = [
                    ['title' => 'Trained operator', 'desc' => 'A Pod24 engineer runs audio, video, and monitoring for your entire session.'],
                    ['title' => 'Broadcast-ready files', 'desc' => 'Individually tracked audio stems and synchronised multi-cam video, delivered within 24 hours.'],
                    ['title' => 'Remote guest support', 'desc' => 'Dial in co-hosts or guests from anywhere — studio-grade quality preserved.'],
                    ['title' => 'Guest headphones & snacks', 'desc' => 'Comfort kit for up to four hosts. Water, coffee, Pod24 merch on request.'],
                    ['title' => 'Post-production (add-on)', 'desc' => 'Editing, clips, subtitles, cover art, distribution — priced separately in the booking flow.'],
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
