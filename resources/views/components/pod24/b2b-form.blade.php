<section id="brands" class="py-20 bg-pod-ink text-white relative overflow-hidden">
    <div class="absolute -bottom-[150px] -left-[150px] w-[500px] h-[500px] pointer-events-none" style="background:radial-gradient(circle,rgba(0,185,227,0.12) 0%,transparent 60%);"></div>

    <div class="max-w-[1200px] mx-auto px-8 relative">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">For brands &amp; events</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-white mb-4">Bring Pod24 to your event.</h2>
            <p class="text-white/65 text-lg max-w-[55ch]">For conferences, brand launches, and corporate offsites &mdash; we bring our team and equipment to your venue across the UAE and GCC. Your guests in, your episodes out, distributed the same week.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-start relative">
            <div>
                <div class="grid grid-cols-2 gap-4">
                    @php
                        $uses = [
                            ['title' => 'Conference activations', 'desc' => 'Record with speakers on the sidelines of your event. Same-day clips for social.'],
                            ['title' => 'Brand content series', 'desc' => 'Branded podcast formats — from strategy to distribution.'],
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

                <div class="flex gap-10 flex-wrap items-center mt-10 opacity-50">
                    <span class="font-bold text-sm tracking-wider">PLACEHOLDER</span>
                    <span class="font-bold text-sm tracking-wider">PLACEHOLDER</span>
                    <span class="font-bold text-sm tracking-wider">PLACEHOLDER</span>
                    <span class="font-bold text-sm tracking-wider">PLACEHOLDER</span>
                    <span class="font-bold text-sm tracking-wider">PLACEHOLDER</span>
                </div>
            </div>

            <form class="bg-white/5 border border-white/10 rounded p-8">
                <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Tell us about your project</div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <input type="text" placeholder="Your name" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent" />
                    <input type="text" placeholder="Company" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent" />
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <input type="email" placeholder="Work email" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent" />
                    <input type="tel" placeholder="Phone (optional)" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent" />
                </div>
                <input type="text" placeholder="Event type (conference / offsite / launch)" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent mb-3" />
                <input type="text" placeholder="Approximate dates" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent mb-3" />
                <textarea rows="4" placeholder="A few lines about the activation" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-pod-accent"></textarea>
                <button type="button" class="mt-4 w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full font-bold text-sm hover:bg-pod-accent-deep hover:text-white transition-all">
                    Request a quote &rarr;
                </button>
            </form>
        </div>
    </div>
</section>
