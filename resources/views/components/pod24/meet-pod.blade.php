<section id="pod" class="py-20 bg-pod-surface">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Meet the studio</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">Built for video, end to end.</h2>
            <p class="text-pod-ink/70 text-lg max-w-[55ch]">Three cameras, live multi-cam switching, broadcast lighting, and broadcast audio engineered to disappear. Walk in with your guests, press record, walk out with a finished video podcast.</p>
        </div>

        <div class="grid lg:grid-cols-[1.2fr_1fr] gap-12">
            <div>
                <div class="aspect-[16/11] rounded-lg relative overflow-hidden">
                    <img src="{{ asset('images/studio/hero.jpg') }}"
                         alt="Pod24 studio interior at Yas Creative Hub"
                         loading="lazy"
                         class="absolute inset-0 w-full h-full object-cover">
                </div>
                <div class="grid grid-cols-4 gap-3 mt-6">
                    @foreach (['01', '02', '03', '05'] as $n)
                        <div class="aspect-square rounded-lg overflow-hidden">
                            <img src="{{ asset("images/studio/thumb-{$n}.jpg") }}"
                                 alt="Pod24 studio · view {{ $n }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-10">
                @php
                    $included = [
                        'Three cameras',
                        'Three microphones',
                        'Flexible professional lighting',
                        'HD/4K video recording',
                        'Delivery of raw footage on external HDD',
                    ];
                    $equipment = [
                        ['Universal Audio dynamic microphones', 'Broadcast-grade dynamics with neutral colour and tight rejection.'],
                        ['Collapsible lantern softboxes', 'Even, soft light across every guest position.'],
                        ['TriCaster system', 'Live multi-cam switching, picture-in-picture, instant replay.'],
                        ['Multichannel dialogue noise suppressor', 'Real-time noise removal across every channel.'],
                    ];
                @endphp

                <div>
                    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-5 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        What's included
                    </div>
                    <ul class="space-y-2.5">
                        @foreach ($included as $item)
                            <li class="flex items-start gap-3 text-pod-ink-deep">
                                <span class="text-pod-accent mt-1" aria-hidden="true">·</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-5 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m6-6h2M4 12H2m13.66-5.66l1.41-1.41M4.93 19.07l1.41-1.41m0-11.32L4.93 4.93m12.73 14.14l1.41 1.41"/>
                        </svg>
                        Studio-grade equipment
                    </div>
                    <div class="space-y-4">
                        @foreach ($equipment as [$title, $desc])
                            <div class="border-l-2 border-pod-border pl-4 hover:border-pod-accent transition-all">
                                <div class="font-bold text-pod-ink-deep">{{ $title }}</div>
                                <div class="text-sm text-pod-ink/70 leading-relaxed">{{ $desc }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
