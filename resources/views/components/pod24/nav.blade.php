@props(['transparent' => false])

@php $status = \App\Support\Pod24Status::current(); @endphp

<nav x-data="{ scrolled: false, mobileOpen: false }"
     x-init="scrolled = window.scrollY > 32; window.addEventListener('scroll', () => scrolled = window.scrollY > 32)"
     :class="(scrolled || ! {{ $transparent ? 'true' : 'false' }}) ? 'bg-white/95 backdrop-blur border-b border-pod-border-soft text-pod-ink-deep' : 'bg-transparent text-white'"
     class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <div class="mx-auto px-6 md:px-8 max-w-[1200px] h-16 flex items-center justify-between gap-6">
        <a href="/" class="flex items-baseline gap-1 font-bold text-xl tracking-tight shrink-0">
            <span>Pod</span><span class="text-pod-accent">24</span>
        </a>

        <div class="hidden md:flex items-center text-sm font-semibold whitespace-nowrap"
             style="column-gap:1.75rem;">
            <a href="/book" class="hover:text-pod-accent transition-colors">Book a session</a>
            <a href="/quote/offsite" class="hover:text-pod-accent transition-colors">Corporate</a>
            <a href="/#pod" class="hover:text-pod-accent transition-colors">The studio</a>
            @auth
                <a href="{{ route('account.dashboard') }}" class="hover:text-pod-accent transition-colors">Account</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-pod-accent transition-colors">Sign in</a>
            @endauth

            <span class="inline-flex items-center"
                  style="gap:0.5rem; padding:0.25rem 0.75rem; border-radius:9999px; border:1px solid currentColor; opacity:0.8; font-size:0.7rem;">
                <span class="relative flex" style="width:0.5rem; height:0.5rem;" aria-hidden="true">
                    @if ($status->isOpen)
                        <span class="absolute inset-0 rounded-full bg-pod-accent opacity-75 animate-ping"></span>
                        <span class="relative rounded-full bg-pod-accent" style="width:0.5rem; height:0.5rem;"></span>
                    @else
                        <span class="relative rounded-full" style="width:0.5rem; height:0.5rem; background:currentColor; opacity:0.5;"></span>
                    @endif
                </span>
                <span class="font-semibold tracking-wide">{{ $status->label }}</span>
            </span>

            <a href="/book"
               class="bg-pod-accent text-pod-ink-deep hover:bg-pod-accent-deep hover:text-white transition-colors"
               style="padding:0.5rem 1rem; border-radius:9999px;">
                Pick a date &rarr;
            </a>
        </div>

        <button @click="mobileOpen = !mobileOpen"
                class="md:hidden p-2 -m-2"
                :aria-expanded="mobileOpen"
                aria-label="Menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display:none"/>
            </svg>
        </button>
    </div>

    <div x-show="mobileOpen" x-cloak
         class="md:hidden bg-white border-t border-pod-border-soft text-pod-ink-deep">
        <div class="px-6 py-4 text-sm font-semibold" style="display:flex;flex-direction:column;gap:0.5rem;">
            <a href="/book" class="block py-2 hover:text-pod-accent">Book a session</a>
            <a href="/quote/offsite" class="block py-2 hover:text-pod-accent">Corporate</a>
            <a href="/#pod" class="block py-2 hover:text-pod-accent">The studio</a>
            @auth
                <a href="{{ route('account.dashboard') }}" class="block py-2 hover:text-pod-accent">Account</a>
            @else
                <a href="{{ route('login') }}" class="block py-2 hover:text-pod-accent">Sign in</a>
            @endauth
        </div>
    </div>
</nav>
