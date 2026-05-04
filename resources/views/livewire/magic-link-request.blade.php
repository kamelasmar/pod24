<section class="container mx-auto py-24 px-4 max-w-md">
<h1 class="text-3xl font-bold mb-6">Sign in to Pod24</h1>
<form action="{{ route('login.magic-link.request') }}" method="POST">
@csrf
<input type="email" name="email" required placeholder="Your email"
       class="w-full border border-pod-border p-3 rounded mb-4" wire:model="email">
<button type="submit" class="w-full bg-pod-accent text-pod-ink-deep p-3 rounded font-bold">
Send sign-in link
</button>
</form>
</section>
