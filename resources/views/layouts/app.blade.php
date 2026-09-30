<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIKMA') — BPMP Gorontalo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-bg text-text font-sans min-h-screen flex flex-col">

    {{-- HEADER --}}
    <header class="bg-primary text-white px-6 py-3 flex items-center gap-4 shadow-lg shrink-0">
        <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo BPMP" class="h-10 w-10 object-contain">
        <div class="flex flex-col leading-tight">
            <span class="font-bold text-sm tracking-wide">BPMP Provinsi Gorontalo</span>
            <span class="text-xs text-sky/80">Sistem Informasi Keluar Masuk (SIKMA)</span>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">

        {{-- SIDEBAR --}}
        @include('components.sidebar')

        <div class="flex flex-col flex-1 overflow-hidden">

            {{-- NAVBAR --}}
            @include('components.navbar')

            {{-- MAIN CONTENT --}}
            <main class="flex-1 overflow-y-auto p-6 bg-bg">
                @yield('content')
            </main>

        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="bg-canvas border-t border-soft px-6 py-2 flex justify-between text-xs text-muted shrink-0">
        <span>BPMP Provinsi Gorontalo &copy; {{ date('Y') }}</span>
        <span>SIKMA v2.0.0</span>
    </footer>

    @stack('scripts')
</body>
</html>
