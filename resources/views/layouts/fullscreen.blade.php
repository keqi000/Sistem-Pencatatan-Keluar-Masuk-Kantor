<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIKMA')</title>
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body class="bg-primary text-white min-h-screen overflow-hidden">

    @yield('content')

    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
