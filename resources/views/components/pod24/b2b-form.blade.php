<section id="brands" class="py-20 bg-pod-ink text-white relative overflow-hidden">
    <div class="absolute -bottom-[150px] -left-[150px] w-[500px] h-[500px] pointer-events-none" style="background:radial-gradient(circle,rgba(0,185,227,0.12) 0%,transparent 60%);"></div>

    <div class="max-w-[1200px] mx-auto px-8 relative">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Corporate podcast services</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-white mb-4">Your podcast strategy, end to end.</h2>
            <p class="text-white/65 text-lg max-w-[55ch]">Branded video series, executive content, internal comms, and event-side recordings. We handle production at our studio or on location at your venue, plus a full post-production stack: editing, subtitles in English & Arabic, distribution, branding, podcast launch.</p>
        </div>

        {{-- Client logo strip. Drop logo files (svg/png/jpg) at public/images/logos/{slug}.{ext} --}}
        @php
            $clients = [
                ['slug' => 'doh', 'name' => 'Department of Health'],
                ['slug' => 'aldar', 'name' => 'Aldar'],
                ['slug' => 'adx', 'name' => 'ADX'],
                ['slug' => 'partner', 'name' => 'Partner'],
                ['slug' => 'adnec', 'name' => 'ADNEC Group'],
            ];

            // Auto-discover logo file (any of svg/png/jpg) for each client
            foreach ($clients as &$c) {
                $c['file'] = collect(['svg', 'png', 'jpg', 'jpeg', 'webp'])
                    ->map(fn ($ext) => "images/logos/{$c['slug']}.{$ext}")
                    ->first(fn ($path) => file_exists(public_path($path)));
            }
            unset($c);
        @endphp
        <div class="mb-12 pb-12 border-b border-white/10">
            <div class="text-xs uppercase tracking-[0.2em] text-white/40 font-bold mb-6">Trusted by</div>
            <div class="flex flex-wrap gap-x-10 gap-y-6 items-center">
                @foreach ($clients as $client)
                    @if ($client['file'])
                        <img src="{{ asset($client['file']) }}"
                             alt="{{ $client['name'] }}"
                             class="h-9 md:h-11 w-auto opacity-85 hover:opacity-100 transition-all">
                    @else
                        <div class="text-white/70 hover:text-white transition-all"
                             title="Drop logo at public/images/logos/{{ $client['slug'] }}.svg (or .png)">
                            <span class="text-xl md:text-2xl font-bold tracking-wider">{{ $client['name'] }}</span>
                        </div>
                    @endif
                @endforeach
                <span class="text-xs text-white/30 italic">+ growing</span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-start relative">
            <div>
                <div class="grid grid-cols-2 gap-4 mb-10">
                    @php
                        $uses = [
                            ['title' => 'Conference activations', 'desc' => 'Record with speakers on the sidelines of your event. Same-day clips for social.'],
                            ['title' => 'Brand content series', 'desc' => 'Branded podcast formats - from strategy to distribution.'],
                            ['title' => 'Corporate offsites', 'desc' => 'Leadership conversations, internal communications, team storytelling.'],
                            ['title' => 'Media partnerships', 'desc' => 'Co-produced content with publishers, studios, and creator networks.'],
                        ];
                    @endphp
                    @foreach ($uses as $use)
                        <div class="bg-white/5 border border-white/10 p-5 rounded transition-all duration-200 hover:border-pod-accent">
                            <h4 class="text-pod-accent text-sm font-bold mb-1.5">{{ $use['title'] }}</h4>
                            <p class="text-xs text-white/65 leading-relaxed">{{ $use['desc'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="text-xs uppercase tracking-[0.2em] text-white/40 font-bold mb-3">Production catalog includes</div>
                <ul class="text-sm text-white/75 space-y-1.5 leading-relaxed">
                    <li>· Standard episode editing (1h / 2h / 3h)</li>
                    <li>· Standard highlights pack (3 reels)</li>
                    <li>· Subtitles & translation - English & Arabic</li>
                    <li>· Custom jingles and podcast intros</li>
                    <li>· Branding package & podcast platform setup</li>
                    <li>· Multi-platform content distribution</li>
                </ul>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-lg p-8 md:p-10">
                <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Custom-quoted</div>
                <h3 class="text-2xl md:text-3xl font-bold text-white tracking-tight leading-tight mb-4">No two corporate briefs are the same.</h3>
                <p class="text-white/65 leading-relaxed mb-8">Every engagement is scoped to your event, audience, and rollout plan. Tell us what you're producing - we'll come back inside 24 hours with a tailored quote.</p>

                <a href="{{ route('quote.offsite') }}" class="block w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full text-center font-bold hover:bg-white transition-all">
                    Browse services & request a quote →
                </a>
                <p class="text-white/40 text-xs text-center mt-3">Or email <a href="mailto:hello@pod24.kamelasmar.com" class="underline hover:text-pod-accent">hello@pod24.kamelasmar.com</a></p>
            </div>
        </div>
    </div>
</section>
