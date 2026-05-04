<section id="how" class="py-20 bg-white">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">How it works</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">Three steps to a finished episode.</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-10 mt-12">
            @php
                $steps = [
                    ['num' => 'Step 01', 'title' => 'Pick your slot', 'desc' => 'Choose a date and package in the booking calendar below. Hourly, half-day, and full-day options.'],
                    ['num' => 'Step 02', 'title' => 'Walk in', 'desc' => 'Show up at Yas Creative Hub. Mics tested, cameras calibrated, ready before you sit down.'],
                    ['num' => 'Step 03', 'title' => 'You press record', 'desc' => 'Focus on your guests. A trained operator handles sound and video. You leave with broadcast-ready files.'],
                ];
            @endphp
            @foreach ($steps as $step)
                <div>
                    <div class="border-t-2 border-pod-accent w-9 mb-6"></div>
                    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">{{ $step['num'] }}</div>
                    <h3 class="text-xl mb-2 tracking-tight font-bold text-pod-ink-deep">{{ $step['title'] }}</h3>
                    <p class="text-pod-ink/70 text-base">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
