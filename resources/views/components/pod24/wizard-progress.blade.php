@props(['step', 'total' => 6, 'labels' => [], 'theme' => 'light'])

@php
    $isDark = $theme === 'dark';
    $mutedClass = $isDark ? 'text-white/50' : 'text-pod-muted';
    $trackClass = $isDark ? 'bg-white/15' : 'bg-pod-border';
@endphp

<div class="mb-10">
    <div class="flex items-center justify-between mb-2 text-xs {{ $mutedClass }}">
        <span class="font-semibold tracking-wide">STEP {{ $step }} OF {{ $total }}</span>
        <span>{{ $labels[$step - 1] ?? '' }}</span>
    </div>
    <div class="flex gap-1.5">
        @for ($i = 1; $i <= $total; $i++)
            <div @class([
                'h-1 flex-1 rounded-full transition-all duration-300',
                'bg-pod-accent' => $i <= $step,
                $trackClass => $i > $step,
            ])></div>
        @endfor
    </div>
</div>
