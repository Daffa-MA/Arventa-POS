<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Arventa POS Admin' }}</title>
    <meta name="arventa-build" content="{{ env('ARVENTA_BUILD_SHA', 'local') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('arventa-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('arventa-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('arventa-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
    <div class="arventa-turbo-indicator fixed inset-x-0 top-0 z-[80] h-0.5" style="background-color: var(--accent, #0f172a)"></div>
    <main id="arventa-admin-main" class="arventa-page-shell min-h-screen w-full">
        {{ $slot }}
    </main>
</body>
</html>
