<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Prologue Alert System') }}</title>

    {{-- @env('local')
    <script src="http://localhost:8098"></script>
    @endenv --}}

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <link
        href="https://fonts.bunny.net/css?family=figtree:300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|jetbrains-mono:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @routes

    @vite([
    'resources/js/app.js',
    'resources/css/app.css',
    ])

    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
