<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pod24 · Video podcast studio at Yas Creative Hub')</title>
    <meta name="description" content="@yield('description', 'Pod24 is a video-first podcast studio at Yas Creative Hub, Abu Dhabi. Three cameras, HD/4K, live multi-cam switching. Walk in, press record, walk out with finished video.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://js.stripe.com/v3/" async></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-pod text-pod-ink bg-pod-bg antialiased">
    <x-pod24.nav :transparent="request()->routeIs('home')" />
    <div class="@if(! request()->routeIs('home')) pt-16 @endif">
        @yield('content')
    </div>
    @livewireScripts
</body>
</html>
