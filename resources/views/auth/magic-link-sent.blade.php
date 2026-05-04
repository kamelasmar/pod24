@extends('pod24.layouts.public')

@section('content')
<section class="container mx-auto py-24 px-4 max-w-md text-center">
    <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-pod-accent-soft flex items-center justify-center" aria-hidden="true">
        <svg class="w-8 h-8 text-pod-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </div>
    <h1 class="text-3xl font-bold tracking-tight mb-3">Check your email</h1>
    <p class="text-pod-muted">We've sent you a sign-in link. It expires in 30 minutes.</p>
    <p class="text-sm text-pod-muted mt-8">
        Didn't get it? <a href="{{ route('login') }}" class="text-pod-accent font-bold hover:underline">Try again</a>
    </p>
</section>
@endsection
