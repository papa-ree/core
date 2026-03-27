@props([
    'illustration' => null,
    'badge' => null,
    'title' => null,
    'description' => null,
    'info' => null,
    'actions' => null,
    'shimmerClass' => 'shimmer-text'
])

<div class="flex justify-center mb-8 slide-up">
    <div class="relative floatAnim">
        {{ $illustration }}
    </div>
</div>

<div class="card-glass rounded-3xl p-8 shadow-2xl slide-up-d1">
    {{-- Badge --}}
    @if($badge)
        <div class="flex justify-center mb-5">
            {{ $badge }}
        </div>
    @endif

    {{-- Heading --}}
    <h1 class="text-center text-3xl font-extrabold text-white mb-2 slide-up-d1">
        <span class="{{ $shimmerClass }}">
            {{ $title }}
        </span>
    </h1>

    {{--Description --}}
    <p class="text-center text-white/60 text-sm leading-relaxed mb-8 max-w-sm mx-auto slide-up-d2">
        {{ $description }}
    </p>

    {{-- Info box --}}
    @if($info)
        <div {{ $info->attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl p-4 mb-8 slide-up-d2']) }}
             style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);">
            {{ $info }}
        </div>
    @endif

    {{-- Action buttons --}}
    <div class="flex flex-col sm:flex-row gap-3 slide-up-d2">
        {{ $actions }}
    </div>
</div>
