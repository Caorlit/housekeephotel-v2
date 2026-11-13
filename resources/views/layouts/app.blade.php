<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titolo', 'Housekeep') · Housekeep Hotel</title>
    <link rel="icon" href="/icons/icon-192.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 antialiased min-h-dvh">
    @auth
        @php
            $voci = auth()->user()->isSuperadmin()
                ? ['admin.index' => 'Hotel', 'admin.statistiche' => 'Statistiche']
                : [
                    'reception.tabellone' => 'Tabellone',
                    'reception.giornata' => 'Preparazione del giorno',
                    'reception.prenotazioni' => 'Prenotazioni',
                    'reception.addette' => 'Addette',
                    'reception.camere' => 'Camere',
                ];
        @endphp
        <nav class="bg-slate-900 text-white print:hidden">
            <div class="max-w-7xl mx-auto px-4 min-h-14 flex flex-wrap items-center gap-x-1 gap-y-1 py-2">
                <span class="font-bold mr-4">Housekeep</span>
                @foreach ($voci as $rotta => $etichetta)
                    @if (Route::has($rotta))
                        <a href="{{ route($rotta) }}"
                           @class([
                               'px-3 py-2 rounded-lg text-sm',
                               'bg-white/15 text-white' => request()->routeIs($rotta),
                               'text-slate-300 hover:text-white hover:bg-white/10' => ! request()->routeIs($rotta),
                           ])>{{ $etichetta }}</a>
                    @endif
                @endforeach
                <span class="ml-auto text-slate-400 text-sm hidden sm:inline">{{ auth()->user()->hotel?->nome }}</span>
                <a href="{{ route('profilo') }}" class="ml-3 text-slate-300 hover:text-white text-sm px-2 py-2" title="Il mio profilo">👤 {{ auth()->user()->nome }}</a>
                <form method="POST" action="{{ route('logout') }}" class="ml-3">
                    @csrf
                    <button class="text-slate-300 hover:text-white text-sm px-2 py-2">Esci</button>
                </form>
            </div>
        </nav>
    @endauth
    <main class="max-w-7xl mx-auto p-4">
        @if (session('ok'))
            <div class="mb-4 rounded-lg bg-green-100 text-green-900 px-4 py-3 print:hidden">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 text-red-900 px-4 py-3 print:hidden">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
