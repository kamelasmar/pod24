@php
    $groups = [
        [
            'eyebrow' => 'Episode editing',
            'services' => [
                ['Standard episode editing', '1 hour'],
                ['Standard episode editing', '2 hours'],
                ['Standard episode editing', '3 hours'],
            ],
        ],
        [
            'eyebrow' => 'Distribution & clips',
            'services' => [
                ['Standard highlights pack', '3 reels'],
                ['Content distribution', 'Multi-platform publish'],
            ],
        ],
        [
            'eyebrow' => 'Translation & subtitling',
            'services' => [
                ['Translation + subtitling', '30 min'],
                ['Translation + subtitling', '1 hour'],
                ['Subtitles — English', '30 min'],
                ['Subtitles — English', '1 hour'],
                ['Subtitles — Arabic', '30 min'],
                ['Subtitles — Arabic', '1 hour'],
            ],
        ],
        [
            'eyebrow' => 'Audio production',
            'services' => [
                ['Custom jingle', 'Composed for your show'],
                ['Podcast intro', 'Voiced + mastered'],
            ],
        ],
        [
            'eyebrow' => 'Brand & launch',
            'services' => [
                ['Branding package', 'Identity + assets'],
                ['Podcast platform setup', 'End-to-end launch'],
            ],
        ],
    ];
@endphp

<div class="space-y-12">
    @foreach ($groups as $group)
        <div>
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-5 inline-flex items-center gap-2">
                <span class="w-6 h-px bg-pod-accent"></span>
                {{ $group['eyebrow'] }}
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($group['services'] as [$name, $detail])
                    <div class="bg-white border border-pod-border rounded-lg p-5 hover:border-pod-accent transition-all">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold text-pod-ink-deep leading-tight">{{ $name }}</div>
                                <div class="text-sm text-pod-muted mt-1">{{ $detail }}</div>
                            </div>
                            <span class="text-[10px] uppercase tracking-wider text-pod-muted font-semibold whitespace-nowrap mt-1 px-2 py-1 bg-pod-surface rounded">Custom quote</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
