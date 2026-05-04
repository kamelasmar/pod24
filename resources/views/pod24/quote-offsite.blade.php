@extends('pod24.layouts.public')

@section('content')
<section class="container mx-auto py-20 px-4 max-w-5xl">
    <a href="/" class="text-sm text-pod-muted hover:text-pod-ink-deep mb-3 inline-block">← Back to home</a>
    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Corporate & on-location</div>
    <h1 class="text-3xl md:text-5xl font-bold tracking-tight mb-4 max-w-3xl">Production support, end to end.</h1>
    <p class="text-pod-muted text-lg mb-12 max-w-2xl leading-relaxed">
        For conferences, brand activations, corporate offsites, and recurring branded series - Pod24 handles the studio, the shoot, the edit, the subtitles, and everything in between. Tell us what you're producing and we'll come back with a custom quote within 24 hours.
    </p>

    <div class="bg-pod-surface rounded-lg p-6 md:p-8 mb-16 flex items-start gap-5">
        <div class="w-12 h-12 rounded-full bg-pod-accent text-pod-ink-deep flex items-center justify-center flex-shrink-0 font-bold" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="flex-1">
            <h2 class="font-bold text-pod-ink-deep mb-1">Inquiry wizard coming soon.</h2>
            <p class="text-sm text-pod-muted leading-relaxed">
                In the meantime, email <a href="mailto:pod24@twofour54.com" class="text-pod-accent font-bold hover:underline">pod24@twofour54.com</a> with your dates, location, scope, and a few lines about the project. We'll respond within 24 hours.
            </p>
        </div>
    </div>

    <div class="mb-16">
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight mb-2">Services available.</h2>
        <p class="text-pod-muted mb-10 max-w-2xl">All services priced by scope and quoted on request. Mix-and-match across packages - nothing is mandatory.</p>
        <x-pod24.corporate-services />
    </div>

    <div class="bg-pod-ink-deep text-white rounded-lg p-8 md:p-12 text-center">
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight mb-3">Have a brief? Let's talk.</h2>
        <p class="text-white/70 mb-6 max-w-xl mx-auto">Tell us your dates, scope, and what success looks like. We'll come back inside 24 hours with a custom proposal.</p>
        <a href="mailto:pod24@twofour54.com"
           class="inline-block bg-pod-accent text-pod-ink-deep px-8 py-4 rounded-full font-bold hover:bg-white transition-all">
            Email pod24@twofour54.com →
        </a>
    </div>
</section>
@endsection
