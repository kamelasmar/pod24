<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pod24 — A broadcast-grade portable podcast pod')</title>
    <meta name="description" content="@yield('description', 'Record your podcast — we bring the studio. Pod24 is a broadcast-grade portable podcast studio delivered across the UAE.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-pod text-pod-ink bg-pod-bg antialiased">
    <div class="bg-pod-ink-deep text-white text-center text-xs tracking-widest py-2 px-6">
        Pod24 · <strong class="text-pod-accent">portable podcast studio</strong> · delivered UAE-wide
    </div>

    <div class="bg-white border-b border-pod-border-soft px-8 py-4 flex justify-between items-center">
        <div class="font-bold tracking-tight text-pod-ink-deep">
            twofour<span class="text-pod-accent">5</span><span class="text-pod-accent">4</span>
        </div>
        <nav class="hidden md:flex gap-8 text-sm text-pod-ink/70 font-medium">
            <a href="#about">About</a>
            <a href="#setup">Business setup</a>
            <a href="#produce">Produce</a>
            <a href="/" class="text-pod-ink font-semibold">Pod24</a>
            <a href="#news">News</a>
        </nav>
        <div class="px-4 py-2 bg-pod-ink-deep text-white rounded-full text-xs font-semibold">Get in touch</div>
    </div>

    @yield('content')

    @livewireScripts
</body>
</html>
