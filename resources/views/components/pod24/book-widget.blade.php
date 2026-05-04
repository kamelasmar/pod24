@props(['facility'])

<section id="book" class="py-20 bg-pod-ink-deep text-white relative overflow-hidden">
    <div class="absolute -top-[200px] -right-[200px] w-[600px] h-[600px] pointer-events-none" style="background:radial-gradient(circle,rgba(0,185,227,0.15) 0%,transparent 60%);"></div>

    <div class="max-w-[1200px] mx-auto px-8 relative">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Book your session</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-white mb-4">Pick a date, pick a package.</h2>
            <p class="text-white/70 text-lg max-w-[55ch]">Live availability. Secure your slot in under a minute &mdash; we'll handle the rest.</p>
        </div>

        <div class="grid md:grid-cols-[1.15fr_1fr] gap-12 items-start relative">
            <livewire:home-booking-widget :facility="$facility" />

            <div>
                <div class="bg-[#242E33] border border-white/10 rounded p-8">
                    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-6">Rates (VAT excl.)</div>
                    @php
                        $rates = [
                            ['name' => 'Hourly', 'sub' => 'Min. 2-hour booking', 'amt' => 'AED 450', 'unit' => '/hr'],
                            ['name' => 'Half-day', 'sub' => '4 hours · best value for interviews', 'amt' => 'AED 1,600', 'unit' => ''],
                            ['name' => 'Full-day', 'sub' => '8 hours · multi-episode shoots', 'amt' => 'AED 2,900', 'unit' => ''],
                            ['name' => 'Multi-day', 'sub' => 'Consecutive days, discounted', 'amt' => 'From AED 2,600', 'unit' => '/day'],
                        ];
                    @endphp
                    @foreach ($rates as $i => $rate)
                        <div class="flex justify-between items-baseline py-4 @if(! $loop->last) border-b border-white/10 @endif">
                            <div>
                                <div class="text-base font-semibold text-white">{{ $rate['name'] }}</div>
                                <div class="text-sm text-white/50 mt-0.5">{{ $rate['sub'] }}</div>
                            </div>
                            <div class="text-xl font-bold tracking-tight text-pod-accent">
                                {{ $rate['amt'] }}<span class="text-xs font-medium text-white/50 ml-1">{{ $rate['unit'] }}</span>
                            </div>
                        </div>
                    @endforeach
                    <div class="mt-5 text-xs text-white/50 leading-relaxed">
                        Includes delivery within Abu Dhabi &amp; Dubai, operator, and broadcast-ready files. Post-production add-ons priced separately at checkout.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
