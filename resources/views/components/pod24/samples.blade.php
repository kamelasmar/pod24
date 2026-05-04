<section class="py-20 bg-pod-surface">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Made at Pod24</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">Hear what's been recorded.</h2>
            <p class="text-pod-ink-70 text-lg max-w-[55ch]">A handful of episodes recorded inside Pod24 — across interview, brand-content, and corporate formats. Real audio, real video, real broadcast quality.</p>
        </div>

        @php
            // Real samples replace these once a few episodes are recorded + cleared for showcase.
            // Each entry: cover gradient, show, host, tier, length, link (placeholder #).
            $samples = [
                ['initials' => 'YH', 'show' => 'Your Hosts Here', 'host' => 'Available for first guests', 'tier' => 'Live Mix + Edit', 'length' => 'Coming soon', 'gradient' => 'from-pod-accent to-pod-accent-deep'],
                ['initials' => 'BS', 'show' => 'Brand Series Pilot', 'host' => 'TBA', 'tier' => 'Live Mix + Edit + Stream', 'length' => 'Coming soon', 'gradient' => 'from-pod-ink-deep to-pod-ink'],
                ['initials' => 'CF', 'show' => 'Corporate Fireside', 'host' => 'TBA', 'tier' => 'Recording Only', 'length' => 'Coming soon', 'gradient' => 'from-slate-700 to-pod-ink-deep'],
                ['initials' => 'IN', 'show' => 'Interview Showcase', 'host' => 'TBA', 'tier' => 'Live Mix', 'length' => 'Coming soon', 'gradient' => 'from-pod-accent-deep to-slate-800'],
            ];
        @endphp

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($samples as $s)
                <div class="bg-white border border-pod-border rounded-lg overflow-hidden hover:border-pod-accent transition-all group">
                    <div class="aspect-square bg-gradient-to-br {{ $s['gradient'] }} flex items-center justify-center relative">
                        <span class="text-5xl font-bold text-white/90 tracking-tight">{{ $s['initials'] }}</span>
                        <div class="absolute inset-0 bg-pod-ink-deep/0 group-hover:bg-pod-ink-deep/40 transition-all flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-all w-14 h-14 rounded-full bg-pod-accent flex items-center justify-center" aria-hidden="true">
                                <svg class="w-6 h-6 text-pod-ink-deep ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="font-bold text-pod-ink-deep mb-0.5 truncate">{{ $s['show'] }}</div>
                        <div class="text-xs text-pod-muted mb-3 truncate">{{ $s['host'] }}</div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="bg-pod-accent-soft text-pod-accent-deep px-2 py-0.5 rounded font-semibold uppercase tracking-wider">{{ $s['tier'] }}</span>
                            <span class="text-pod-muted">{{ $s['length'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('book') }}" class="inline-block text-sm text-pod-ink-deep font-bold hover:text-pod-accent transition-all underline underline-offset-4">
                Be on the next set of samples — book a session →
            </a>
        </div>
    </div>
</section>
