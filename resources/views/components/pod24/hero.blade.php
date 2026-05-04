{{-- Studio status used to live here; moved into the nav. --}}

<section class="relative min-h-[calc(100vh-64px)] bg-black overflow-hidden flex items-center justify-center text-white text-center">
    <div class="absolute inset-0 bg-cover bg-center"
         style="background-image:url('{{ asset('images/studio/hero.jpg') }}');"></div>
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.55) 0%,rgba(0,0,0,0.65) 50%,rgba(0,0,0,0.85) 100%);"></div>
    <div class="absolute -top-[10%] left-1/2 -translate-x-1/2 w-[900px] h-[700px] pointer-events-none" style="background:radial-gradient(ellipse,rgba(0,185,227,0.18) 0%,transparent 55%);animation:pod-pulse 5s ease-in-out infinite;"></div>

    <div class="relative z-10 px-8 py-16 max-w-[960px]">
        <div class="text-[0.72rem] uppercase tracking-[0.25em] text-pod-accent font-bold mb-7 inline-flex items-center gap-3 opacity-0" style="animation:fadeIn 0.6s ease-out 0.1s forwards;">
            Pod24 &middot; Video podcast studio
        </div>

        <h1 class="text-5xl md:text-7xl leading-[1.05] tracking-tight font-bold mb-6 text-white opacity-0" style="animation:fadeIn 0.7s ease-out 0.25s forwards;">
            Your
            <span x-data="{
                    words: ['video podcast', 'interview show', 'branded series', 'corporate brief'],
                    current: 'video podcast',
                    visible: true,
                    init() {
                        let i = 0;
                        setInterval(() => {
                            this.visible = false;
                            setTimeout(() => {
                                i = (i + 1) % this.words.length;
                                this.current = this.words[i];
                                this.visible = true;
                            }, 300);
                        }, 2400);
                    }
                 }"
                 class="text-pod-accent inline-block transition-opacity duration-300"
                 :class="visible ? 'opacity-100' : 'opacity-0'"
                 x-text="current">video podcast</span>,<br>
            <em class="not-italic">made on the day.</em>
        </h1>

        <div class="flex flex-wrap justify-center mt-10 mb-9 opacity-0" style="animation:fadeIn 0.8s ease-out 0.45s forwards; gap:0.75rem;">
            <a href="/book" class="bg-pod-accent text-pod-ink-deep rounded-full font-bold text-base hover:bg-white transition-colors" style="padding:1rem 2rem;">
                Book a session &rarr;
            </a>
            <a href="/quote/offsite" class="border border-white/30 text-white rounded-full font-bold text-base hover:border-pod-accent hover:text-pod-accent transition-colors" style="padding:1rem 2rem;">
                For corporate teams
            </a>
        </div>

        <div class="flex flex-wrap justify-center text-sm text-white/65 font-medium opacity-0" style="animation:fadeIn 0.9s ease-out 0.8s forwards; column-gap:2rem; row-gap:0.75rem;">
            <span class="inline-flex items-center gap-2 before:content-[''] before:w-1.5 before:h-1.5 before:rounded-full before:bg-pod-accent">3 cameras &middot; HD/4K</span>
            <span class="inline-flex items-center gap-2 before:content-[''] before:w-1.5 before:h-1.5 before:rounded-full before:bg-pod-accent">Live multi-cam switching</span>
            <span class="inline-flex items-center gap-2 before:content-[''] before:w-1.5 before:h-1.5 before:rounded-full before:bg-pod-accent">From AED 254/hour</span>
        </div>
    </div>

    <a href="#book" class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 text-white/50 hover:text-pod-accent transition-colors text-xs tracking-[0.25em] uppercase flex flex-col items-center gap-2 opacity-0" style="animation:fadeIn 0.6s ease-out 1.1s forwards;">
        Scroll
        <span class="block w-px h-8 bg-gradient-to-b from-current to-transparent"></span>
    </a>
</section>

<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pod-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
</style>
