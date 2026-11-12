<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titolo', 'Housekeep') · Housekeep Hotel</title>
    <link rel="icon" href="/icons/icon-192.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 antialiased min-h-dvh">
    @auth
        <nav class="bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 h-14 flex items-center gap-6">
                <span class="font-bold">Housekeep</span>
                @if (auth()->user()->isSuperadmin())
                    <a href="{{ route('admin.index') }}" class="text-slate-300 hover:text-white">Amministrazione</a>
                @else
                    <a href="{{ route('reception.tabellone') }}" class="text-slate-300 hover:text-white">Tabellone</a>
                    <span class="text-slate-400 text-sm">{{ auth()->user()->hotel?->nome }}</span>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button class="text-slate-300 hover:text-white text-sm">Esci</button>
                </form>
            </div>
        </nav>
    @endauth
    <main class="max-w-7xl mx-auto p-4">
        @yield('content')
    </main>
</body>
</html>
