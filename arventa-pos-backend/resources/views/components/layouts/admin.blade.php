<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Arventa POS Admin' }}</title>
    <meta name="arventa-build" content="{{ env('ARVENTA_BUILD_SHA', 'local') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
        $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
    @endphp
    @if ($cssFile)
        <link rel="stylesheet" href="{{ asset('build/'.$cssFile) }}">
    @endif
    @if ($jsFile)
        <script type="module" src="{{ asset('build/'.$jsFile) }}"></script>
    @endif
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
    <main class="min-h-screen w-full">
        {{ $slot }}
    </main>
</body>
</html>
