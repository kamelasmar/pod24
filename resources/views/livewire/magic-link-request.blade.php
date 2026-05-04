<section class="container mx-auto py-24 px-4 max-w-md">
    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Sign in</div>
    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-3">Welcome back to Pod24.</h1>
    <p class="text-pod-muted mb-8">Enter your email and we'll send you a sign-in link. No password required.</p>

    <form action="{{ route('login.magic-link.request') }}" method="POST">
        @csrf
        <label for="login-email" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Email</label>
        <input type="email"
               id="login-email"
               name="email"
               required
               autocomplete="email"
               autofocus
               class="w-full border border-pod-border rounded-lg p-3 mb-5 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
        <button type="submit"
                class="w-full bg-pod-accent text-pod-ink-deep py-4 rounded-full font-bold hover:bg-pod-accent-deep hover:text-white transition-all cursor-pointer">
            Send sign-in link →
        </button>
    </form>

    <p class="text-sm text-pod-muted mt-6 text-center">
        New to Pod24? <a href="/book" class="text-pod-accent font-bold hover:underline">Book a session</a> - we'll create your account at checkout.
    </p>
</section>
