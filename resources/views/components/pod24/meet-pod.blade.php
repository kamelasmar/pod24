<section id="pod" class="py-20 bg-pod-surface">
    <div class="max-w-[1200px] mx-auto px-8">
        <div class="mb-12 max-w-[60ch]">
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-4 inline-flex items-center gap-2 before:content-[''] before:w-6 before:h-0.5 before:bg-pod-accent">Meet the pod</div>
            <h2 class="text-3xl md:text-5xl leading-tight tracking-tight font-bold text-pod-ink-deep mb-4">A complete studio in a pod that travels.</h2>
            <p class="text-pod-ink/70 text-lg max-w-[55ch]">Engineered for broadcast audio and multi-camera video. Compact enough to fit in a boardroom, a ballroom, or a backyard.</p>
        </div>
        <div class="grid md:grid-cols-[1.4fr_1fr] gap-12">
            <div>
                <div class="aspect-[16/11] rounded relative overflow-hidden flex items-center justify-center text-white/30 text-sm tracking-[0.15em] uppercase" style="background:linear-gradient(135deg,#242E33,#000);">
                    Photo &middot; Pod24 interior / exterior
                </div>
                <div class="grid grid-cols-4 gap-3 mt-6">
                    <div class="aspect-square bg-pod-border-soft rounded"></div>
                    <div class="aspect-square bg-pod-border-soft rounded"></div>
                    <div class="aspect-square bg-pod-border-soft rounded"></div>
                    <div class="aspect-square bg-pod-border-soft rounded"></div>
                </div>
            </div>
            <div class="flex flex-col gap-4">
                @php
                    $specs = [
                        ['icon' => '🎙', 'title' => '4× Shure SM7B microphones', 'desc' => 'Broadcast-standard with individual boom arms.'],
                        ['icon' => '📹', 'title' => '4-camera multi-cam setup', 'desc' => '4K recording, angle switching, HDMI/SDI feeds.'],
                        ['icon' => '🎛', 'title' => 'Studio-grade audio interface', 'desc' => 'Rodecaster Pro II · isolated tracks per guest.'],
                        ['icon' => '🌐', 'title' => 'Remote guest support', 'desc' => 'Riverside / Squadcast integration for hybrid recordings.'],
                        ['icon' => '⚡', 'title' => 'Self-powered · 30-min setup', 'desc' => 'All we need is a parking spot or floor space.'],
                    ];
                @endphp
                @foreach ($specs as $spec)
                    <div class="flex gap-4 py-4 border-b border-pod-border">
                        <div class="w-9 h-9 rounded-full bg-pod-accent text-pod-ink-deep flex items-center justify-center font-bold text-sm shrink-0">{{ $spec['icon'] }}</div>
                        <div>
                            <div class="text-base font-semibold text-pod-ink-deep mb-0.5">{{ $spec['title'] }}</div>
                            <div class="text-sm text-pod-ink/70">{{ $spec['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
