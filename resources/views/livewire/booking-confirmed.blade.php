<div class="bg-pod-ink-deep text-white min-h-screen -mt-16 pt-24 pb-16 relative overflow-hidden">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[500px] pointer-events-none" style="background:radial-gradient(circle,rgba(0,185,227,0.15) 0%,transparent 60%);"></div>

    <section class="container mx-auto max-w-2xl py-10 px-4 text-center relative">
        @if ($booking)
            <div class="w-20 h-20 mx-auto mb-8 rounded-full bg-pod-accent/15 flex items-center justify-center" aria-hidden="true">
                <svg class="w-10 h-10 text-pod-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Confirmed</div>
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4 text-white">You're booked.</h1>
            <p class="text-white/65 mb-10 max-w-md mx-auto">
                We've sent confirmation to <strong class="text-white">{{ $booking->contact_email }}</strong>. See you at Yas Creative Hub.
            </p>

            <div class="bg-white/5 border border-white/10 rounded-lg p-8 text-left max-w-md mx-auto">
                <div class="flex justify-between items-baseline py-3 border-b border-white/10">
                    <span class="text-sm text-white/50">Reference</span>
                    <span class="font-mono text-sm font-semibold text-white">{{ $booking->ulid }}</span>
                </div>
                <div class="flex justify-between items-baseline py-3 border-b border-white/10">
                    <span class="text-sm text-white/50">Date</span>
                    <span class="font-semibold text-white">{{ $booking->starts_at->format('l, F j') }}</span>
                </div>
                <div class="flex justify-between items-baseline py-3 border-b border-white/10">
                    <span class="text-sm text-white/50">Time</span>
                    <span class="font-semibold text-white">{{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</span>
                </div>
                <div class="flex justify-between items-baseline py-3">
                    <span class="text-sm text-white/50">Total paid</span>
                    <span class="font-bold text-pod-accent text-lg">AED {{ number_format($booking->total_aed_cents / 100, 2) }}</span>
                </div>
            </div>

            <a href="/" class="inline-block mt-10 text-sm text-white/60 hover:text-white underline">← Back to home</a>
        @else
            <h1 class="text-3xl font-bold tracking-tight mb-3 text-white">Booking not found</h1>
            <p class="text-white/65 mb-6">The reference link looks invalid or has expired.</p>
            <a href="/" class="inline-block bg-pod-accent text-pod-ink-deep px-6 py-3 rounded-full font-bold hover:bg-white transition-all">
                ← Back to home
            </a>
        @endif
    </section>
</div>
