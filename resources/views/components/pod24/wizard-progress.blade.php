@props(['step', 'total' => 6, 'labels' => []])

<div class="mb-10">
    <div class="flex items-center justify-between mb-2 text-xs text-pod-muted">
        <span class="font-semibold tracking-wide">STEP {{ $step }} OF {{ $total }}</span>
        <span>{{ $labels[$step - 1] ?? '' }}</span>
    </div>
    <div class="flex gap-1.5">
        @for ($i = 1; $i <= $total; $i++)
            <div @class([
                'h-1 flex-1 rounded-full transition-all duration-300',
                'bg-pod-accent' => $i <= $step,
                'bg-pod-border' => $i > $step,
            ])></div>
        @endfor
    </div>
</div>
