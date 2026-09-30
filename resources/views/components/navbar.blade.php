@php $user = auth()->user(); @endphp

<nav class="bg-canvas border-b border-soft px-5 py-2.5 flex items-center justify-between shrink-0 shadow-sm">
    <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-primary">@yield('title')</span>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs px-2.5 py-0.5 rounded-full bg-soft text-brand font-semibold border border-sky/30">
            {{ ucfirst($user->role) }}
        </span>
        <span class="text-sm text-text font-medium">{{ $user->full_name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="text-xs text-danger hover:text-red-800 font-medium cursor-pointer bg-transparent border-0 p-0 transition-colors">
                Logout
            </button>
        </form>
    </div>
</nav>
