<section class="container mx-auto max-w-2xl py-20 px-4 text-center">
    @if ($booking)
        <div class="w-20 h-20 mx-auto mb-8 rounded-full bg-pod-accent-soft flex items-center justify-center" aria-hidden="true">
            <svg class="w-10 h-10 text-pod-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Confirmed</div>
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4">You're booked.</h1>
        <p class="text-pod-muted mb-10 max-w-md mx-auto">
            We've sent confirmation to <strong class="text-pod-ink-deep">{{ $booking->contact_email }}</strong>. See you at Yas Creative Hub.
        </p>

        <div class="bg-white border border-pod-border rounded-lg p-8 text-left max-w-md mx-auto">
            <div class="flex justify-between items-baseline py-3 border-b border-pod-border-soft">
                <span class="text-sm text-pod-muted">Reference</span>
                <span class="font-mono text-sm font-semibold text-pod-ink-deep">{{ $booking->ulid }}</span>
            </div>
            <div class="flex justify-between items-baseline py-3 border-b border-pod-border-soft">
                <span class="text-sm text-pod-muted">Date</span>
                <span class="font-semibold text-pod-ink-deep">{{ $booking->starts_at->format('l, F j') }}</span>
            </div>
            <div class="flex justify-between items-baseline py-3 border-b border-pod-border-soft">
                <span class="text-sm text-pod-muted">Time</span>
                <span class="font-semibold text-pod-ink-deep">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</span>
            </div>
            <div class="flex justify-between items-baseline py-3">
                <span class="text-sm text-pod-muted">Total paid</span>
                <span class="font-bold text-pod-accent text-lg">AED {{ number_format($booking->total_aed_cents / 100, 2) }}</span>
            </div>
        </div>

        <a href="/" class="inline-block mt-10 text-sm text-pod-muted hover:text-pod-ink-deep underline">← Back to home</a>
    @else
        <h1 class="text-3xl font-bold tracking-tight mb-3">Booking not found</h1>
        <p class="text-pod-muted mb-6">The reference link looks invalid or has expired.</p>
        <a href="/" class="inline-block bg-pod-accent text-pod-ink-deep px-6 py-3 rounded-full font-bold hover:bg-pod-accent-deep hover:text-white transition-all">
            ← Back to home
        </a>
    @endif
</section>
