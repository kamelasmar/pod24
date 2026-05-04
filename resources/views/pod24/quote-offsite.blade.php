@extends('pod24.layouts.public')

@section('content')
<section class="container mx-auto py-24 px-4 max-w-2xl">
    <a href="/" class="text-sm text-pod-muted hover:text-pod-ink-deep mb-3 inline-block">← Back to home</a>
    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">On-location filming</div>
    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-4">We'll bring Pod24 to you.</h1>
    <p class="text-pod-muted mb-10 leading-relaxed">
        For conferences, brand activations, and corporate offsites — our team can deliver a full broadcast setup at your venue across the UAE and GCC. Tell us about your project and we'll respond within 24 hours with a custom quote.
    </p>

    <div class="bg-white border border-pod-border rounded-lg p-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-pod-accent-soft flex items-center justify-center flex-shrink-0" aria-hidden="true">
                <svg class="w-6 h-6 text-pod-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-pod-ink-deep mb-1">A proper request flow is coming.</h2>
                <p class="text-sm text-pod-muted leading-relaxed">For now, email <a href="mailto:hello@pod24.kamelasmar.com" class="text-pod-accent font-bold hover:underline">hello@pod24.kamelasmar.com</a> with your dates, location, and a few lines about the project.</p>
            </div>
        </div>
    </div>
</section>
@endsection
