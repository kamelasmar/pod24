@props(['facility'])

<section id="book" class="py-20 bg-pod-ink-deep text-white relative overflow-hidden">
    <div class="absolute -top-[200px] -right-[200px] w-[600px] h-[600px] pointer-events-none" style="background:radial-gradient(circle,rgba(0,185,227,0.15) 0%,transparent 60%);"></div>

    <div class="max-w-[1200px] mx-auto px-8 relative">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Book your session</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-white mb-4">Pick a date, pick a package.</h2>
            <p class="text-white/70 text-lg max-w-[55ch]">Live availability. Secure your slot in under a minute - we'll handle the rest.</p>
        </div>

        <div class="grid md:grid-cols-[1.15fr_1fr] gap-12 items-start relative">
            <livewire:home-booking-widget :facility="$facility" />

            <div>
                <div class="bg-[#242E33] border border-white/10 rounded p-8">
                    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-6">Rates (VAT excl.)</div>
                    @php
                        $tiers = $facility->serviceTiers()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->get();
                        $tierBlurbs = [
                            'Recording Only' => 'Audio + multi-cam capture, raw stems on HDD',
                            'Live Mix' => 'Live multi-cam switching, ready to publish',
                            'Live Mix + Standard Edit' => 'Switching + same-day standard edit',
                            'Live Mix + Standard Edit + Live Stream' => 'Switching, edit, plus live broadcast feed',
                        ];
                    @endphp
                    @foreach ($tiers as $tier)
                        <div class="flex justify-between items-baseline py-4 @if(! $loop->last) border-b border-white/10 @endif">
                            <div class="pr-4">
                                <div class="text-base font-semibold text-white">{{ $tier->name }}</div>
                                <div class="text-sm text-white/50 mt-0.5">{{ $tierBlurbs[$tier->name] ?? '1 to 8 hours per session' }}</div>
                            </div>
                            <div class="text-xl font-bold tracking-tight text-pod-accent whitespace-nowrap">
                                AED {{ number_format($tier->base_hourly_rate_aed_cents / 100, 0) }}<span class="text-xs font-medium text-white/50 ml-1">/hr</span>
                            </div>
                        </div>
                    @endforeach
                    <div class="mt-5 text-xs text-white/50 leading-relaxed">
                        Sessions are 1-8 hours per day at Yas Creative Hub, Abu Dhabi. Operator and raw footage included. Multi-day shoots and post-production add-ons priced separately at checkout. For on-location filming, see corporate services.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
