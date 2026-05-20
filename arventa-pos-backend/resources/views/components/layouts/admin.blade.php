<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Arventa POS Admin' }}</title>
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $assetEntry = function (string $entry) use ($manifest) {
            $normalizedEntry = str_replace('\\', '/', $entry);

            foreach ($manifest as $key => $value) {
                $normalizedKey = str_replace('\\', '/', $key);
                $normalizedSrc = str_replace('\\', '/', $value['src'] ?? '');

                if ($normalizedKey === $normalizedEntry || str_ends_with($normalizedKey, '/'.$normalizedEntry) || $normalizedSrc === $normalizedEntry || str_ends_with($normalizedSrc, '/'.$normalizedEntry)) {
                    return $value;
                }
            }

            return null;
        };
        $cssAsset = $assetEntry('resources/css/app.css');
        $jsAsset = $assetEntry('resources/js/app.js');
    @endphp
    @if ($cssAsset)
        <link rel="stylesheet" href="{{ asset('build/'.$cssAsset['file']) }}">
    @endif
    @if ($jsAsset)
        <script type="module" src="{{ asset('build/'.$jsAsset['file']) }}"></script>
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
