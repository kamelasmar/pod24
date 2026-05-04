<section class="container mx-auto py-16 px-4 max-w-5xl">
    <div class="flex flex-wrap justify-between items-start gap-4 mb-12">
        <div>
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-2">Account</div>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Welcome, {{ $user->name ?: explode('@', $user->email)[0] }}</h1>
            <p class="text-pod-muted mt-1 text-sm">{{ $user->email }}</p>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-sm text-pod-muted hover:text-pod-ink-deep underline cursor-pointer">Sign out</button>
        </form>
    </div>

    <div class="grid md:grid-cols-3 gap-3 mb-16">
        <a href="{{ route('book') }}"
           class="bg-pod-ink-deep text-white rounded-lg p-6 hover:bg-pod-accent hover:text-pod-ink-deep transition-all cursor-pointer flex items-center justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-[0.15em] opacity-70 font-bold mb-1">Book</div>
                <div class="font-bold text-lg">New session</div>
            </div>
            <span class="text-xl">→</span>
        </a>
        <a href="{{ route('account.packs') }}"
           class="bg-white border border-pod-border rounded-lg p-6 hover:border-pod-accent transition-all cursor-pointer flex items-center justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-1">Buy</div>
                <div class="font-bold text-lg text-pod-ink-deep">Hour pack</div>
            </div>
            <span class="text-pod-accent text-xl">→</span>
        </a>
        <a href="{{ route('quote.offsite') }}"
           class="bg-white border border-pod-border rounded-lg p-6 hover:border-pod-accent transition-all cursor-pointer flex items-center justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-1">Off-site</div>
                <div class="font-bold text-lg text-pod-ink-deep">Get a quote</div>
            </div>
            <span class="text-pod-accent text-xl">→</span>
        </a>
    </div>

    <div class="mb-12">
        <div class="flex items-end justify-between mb-4">
            <h2 class="text-xl font-bold tracking-tight">Hour pack balance</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-3">
            @forelse ($balances as $slug => $b)
                @php $hasHours = $b['hours'] > 0; @endphp
                <div @class([
                    'rounded-lg p-5 border',
                    'bg-pod-accent-soft border-pod-accent' => $hasHours,
                    'bg-white border-pod-border' => ! $hasHours,
                ])>
                    <div class="text-sm text-pod-muted mb-1">{{ $b['name'] }}</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold tracking-tight text-pod-ink-deep">{{ $b['hours'] }}</span>
                        <span class="text-pod-muted text-sm">hours</span>
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 text-pod-muted text-sm">No facilities configured.</div>
            @endforelse
        </div>
    </div>

    <div>
        <h2 class="text-xl font-bold tracking-tight mb-4">Your bookings</h2>
        @forelse ($bookings as $booking)
            @php
                $statusLabel = ucfirst(str_replace('_', ' ', $booking->status->value));
                $statusClasses = match ($booking->status->value) {
                    'confirmed' => 'bg-emerald-100 text-emerald-800',
                    'completed' => 'bg-blue-100 text-blue-800',
                    'pending_payment' => 'bg-amber-100 text-amber-800',
                    'hold' => 'bg-gray-100 text-gray-700',
                    'cancelled' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-700',
                };
            @endphp
            <div class="border border-pod-border rounded-lg p-5 mb-3 flex flex-wrap items-center justify-between gap-4 hover:border-pod-accent/50 transition-all">
                <div class="min-w-0">
                    <div class="font-bold text-pod-ink-deep">{{ $booking->starts_at->format('l, F j · H:i') }}</div>
                    <div class="text-xs text-pod-muted mt-1 font-mono">{{ $booking->ulid }}</div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-semibold uppercase tracking-wide {{ $statusClasses }}">{{ $statusLabel }}</span>
                <div class="text-right font-bold tracking-tight text-pod-ink-deep">
                    AED {{ number_format($booking->total_aed_cents / 100, 2) }}
                </div>
            </div>
        @empty
            <div class="border-2 border-dashed border-pod-border rounded-lg p-8 text-center">
                <p class="text-pod-muted mb-4">You haven't booked a session yet.</p>
                <a href="{{ route('book') }}"
                   class="inline-block bg-pod-accent text-pod-ink-deep px-6 py-3 rounded-full font-bold hover:bg-pod-accent-deep hover:text-white transition-all cursor-pointer">
                    Book your first session →
                </a>
            </div>
        @endforelse
    </div>
</section>
