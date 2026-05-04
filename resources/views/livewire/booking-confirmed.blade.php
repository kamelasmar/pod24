<div class="container mx-auto max-w-2xl py-16 text-center">
    @if ($booking)
        <h1 class="text-4xl font-bold mb-4">Booking confirmed</h1>
        <p class="text-pod-muted mb-8">
            We've sent confirmation to <strong>{{ $booking->contact_email }}</strong>.
        </p>
        <div class="border border-pod-border rounded p-6 inline-block">
            <div><strong>Reference:</strong> {{ $booking->ulid }}</div>
            <div><strong>Date:</strong> {{ $booking->starts_at->format('l, F j') }}</div>
            <div><strong>Time:</strong> {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</div>
            <div><strong>Total:</strong> AED {{ number_format($booking->total_aed_cents / 100, 2) }}</div>
        </div>
    @else
        <h1 class="text-2xl font-bold">Booking not found.</h1>
    @endif
</div>
