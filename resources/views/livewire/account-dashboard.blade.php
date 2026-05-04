<section class="container mx-auto py-12 px-4 max-w-4xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Welcome, {{ $user->name ?: $user->email }}</h1>
        <form action="{{ route('logout') }}" method="POST">@csrf
            <button class="text-pod-muted underline">Sign out</button>
        </form>
    </div>

    <h2 class="text-xl font-bold mb-4">Hour pack balance</h2>
    <div class="grid md:grid-cols-2 gap-4 mb-12">
        @forelse ($balances as $slug => $b)
            <div class="border border-pod-border rounded p-4">
                <div class="text-sm text-pod-muted">{{ $b['name'] }}</div>
                <div class="text-2xl font-bold">{{ $b['hours'] }} hours</div>
            </div>
        @empty
            <p class="text-pod-muted">No active packs.</p>
        @endforelse
    </div>

    <h2 class="text-xl font-bold mb-4">Your bookings</h2>
    <div class="space-y-2">
        @forelse ($bookings as $booking)
            <div class="border border-pod-border rounded p-4 flex justify-between">
                <div>
                    <div class="font-bold">{{ $booking->starts_at->format('l, F j, H:i') }}</div>
                    <div class="text-sm text-pod-muted">{{ $booking->ulid }} · {{ ucfirst(str_replace('_', ' ', $booking->status->value)) }}</div>
                </div>
                <div class="text-right">
                    AED {{ number_format($booking->total_aed_cents / 100, 2) }}
                </div>
            </div>
        @empty
            <p class="text-pod-muted">No bookings yet. <a href="{{ route('book') }}" class="underline">Book your first session →</a></p>
        @endforelse
    </div>
</section>
