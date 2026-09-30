<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIKMA BPMP Gorontalo</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-soft flex items-center justify-center p-4">

    <div class="w-full max-w-4xl bg-canvas rounded-2xl shadow-2xl overflow-hidden flex min-h-[520px]">

        {{-- PANEL KIRI — Branding --}}
        <div class="hidden md:flex flex-col justify-between w-5/12 p-10"
             style="background: linear-gradient(160deg, #0a2e5c 0%, #0d3a73 60%, #0073e6 100%);">

            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo BPMP"
                     class="h-12 w-12 object-contain drop-shadow">
                <div class="leading-tight">
                    <p class="text-white font-bold text-sm">BPMP Provinsi</p>
                    <p class="text-sky font-bold text-sm">Gorontalo</p>
                </div>
            </div>

            <div>
                <h1 class="text-white text-3xl font-bold leading-snug mb-3">
                    Sistem Informasi<br>Keluar Masuk
                </h1>
                <p class="text-white/60 text-sm leading-relaxed">
                    Pencatatan aktivitas keluar masuk pegawai berbasis QR Code dinamis.
                </p>
            </div>

            <p class="text-white/30 text-xs">SIKMA v2.0.0 &copy; {{ date('Y') }}</p>
        </div>

        {{-- PANEL KANAN — Form Login --}}
        <div class="flex-1 flex flex-col justify-center px-8 py-10 md:px-12">

            {{-- Mobile header --}}
            <div class="flex md:hidden items-center gap-3 mb-8">
                <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo BPMP" class="h-9 w-9 object-contain">
                <div class="leading-tight">
                    <p class="text-primary font-bold text-sm">BPMP Provinsi Gorontalo</p>
                    <p class="text-muted text-xs">SIKMA v2.0.0</p>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-primary mb-1">Selamat Datang</h2>
            <p class="text-muted text-sm mb-8">Masuk dengan akun kepegawaian Anda</p>

            {{-- Error --}}
            @if($errors->any())
                <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-danger text-sm flex items-start gap-2">
                    <span class="mt-0.5">⚠</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="/login" class="flex flex-col gap-5">
                @csrf

                {{-- Username --}}
                <div class="flex flex-col gap-1.5">
                    <label for="username" class="text-sm font-semibold text-primary">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan username"
                        class="w-full px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand
                               placeholder:text-muted/50 transition"
                    >
                </div>

                {{-- Password --}}
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-semibold text-primary">Kata Sandi</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="w-full px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand
                               placeholder:text-muted/50 transition"
                    >
                </div>

                {{-- Remember --}}
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="remember"
                           class="w-4 h-4 rounded border-sky/40 accent-brand">
                    <span class="text-sm text-muted">Ingat saya</span>
                </label>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full py-2.5 rounded-lg bg-brand text-white font-semibold text-sm
                           hover:bg-blue-700 active:scale-[0.98] transition-all shadow-md shadow-brand/30 cursor-pointer">
                    Masuk
                </button>
            </form>

            <p class="mt-8 text-xs text-muted text-center">
                Lupa akun? Hubungi <span class="text-brand font-medium">Admin Kepegawaian</span>
            </p>
        </div>
    </div>

</body>
</html>
