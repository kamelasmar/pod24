<div id="stickyCta" class="fixed left-0 right-0 bottom-0 z-[100] text-white px-8 py-3.5 flex justify-between items-center gap-6 border-t border-white/10 shadow-2xl"
     style="background:rgba(28,35,39,0.96); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); transform:translateY(100%); transition:transform 0.35s cubic-bezier(.2,.8,.2,1);"
     data-sticky-cta>
    <div class="flex items-center gap-5 min-w-0">
        <div class="text-sm font-bold tracking-tight whitespace-nowrap">
            Pod24 - Yas Creative Hub
        </div>
        <div class="hidden md:flex gap-5 text-xs text-white/65 font-medium">
            <span class="text-white font-bold inline-flex items-center gap-2 before:content-[''] before:w-1 before:h-1 before:rounded-full before:bg-pod-accent whitespace-nowrap">From AED 254/hr</span>
            <span class="inline-flex items-center gap-2 before:content-[''] before:w-1 before:h-1 before:rounded-full before:bg-pod-accent whitespace-nowrap">Operator included</span>
            <span class="inline-flex items-center gap-2 before:content-[''] before:w-1 before:h-1 before:rounded-full before:bg-pod-accent whitespace-nowrap">Yas Creative Hub, Abu Dhabi</span>
        </div>
    </div>
    <div class="flex items-center gap-3 shrink-0">
        <a href="#brands" class="hidden md:inline-block text-white/80 text-sm font-semibold py-2 border-b border-transparent hover:text-pod-accent hover:border-pod-accent transition-all">Brands & events &rarr;</a>
        <a href="#book" class="bg-pod-accent text-pod-ink-deep px-5 py-3 rounded-full font-bold text-sm inline-flex items-center gap-2 hover:bg-white transition-all">Book your session &rarr;</a>
    </div>
</div>

<style>
    [data-sticky-cta].is-visible { transform: translateY(0) !important; }
</style>
<script>
    (function () {
        const bar = document.querySelector('[data-sticky-cta]');
        if (!bar) return;
        const hero = document.querySelector('section');
        if (!hero) return;
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) bar.classList.add('is-visible');
                else bar.classList.remove('is-visible');
            });
        }, { threshold: 0, rootMargin: '-20% 0px 0px 0px' });
        observer.observe(hero);
    })();
</script>
