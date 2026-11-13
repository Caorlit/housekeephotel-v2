@extends('layouts.app')
@section('titolo', 'Anteprima import')

@section('content')
@php
    $stili = [
        'nuova' => ['Nuova', 'bg-green-100 text-green-900'],
        'aggiornata' => ['Aggiornata', 'bg-blue-100 text-blue-900'],
        'invariata' => ['Già presente', 'bg-slate-100 text-slate-600'],
        'scartata' => ['Scartata', 'bg-red-100 text-red-900'],
    ];
    $daSalvare = ($conteggi['nuova'] ?? 0) + ($conteggi['aggiornata'] ?? 0);
@endphp
<a href="{{ route('reception.prenotazioni.importa') }}" class="text-sm underline text-slate-600">← Scegli un altro file</a>
<h1 class="text-2xl font-bold mt-2 mb-4">Anteprima dell'import</h1>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($stili as $esito => [$nome, $classi])
        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $classi }}">{{ $nome }}: {{ $conteggi[$esito] ?? 0 }}</span>
    @endforeach
    <span class="rounded-full px-3 py-1 text-sm bg-white">Riassetto nuove: {{ $riassetto }}</span>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-x-auto mb-4">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 border-b">
            <tr><th class="p-2">Riga</th><th class="p-2">Camera</th><th class="p-2">Arrivo</th><th class="p-2">Partenza</th><th class="p-2">Codice</th><th class="p-2">Pax</th><th class="p-2">Esito</th></tr>
        </thead>
        <tbody class="divide-y">
            @foreach ($righe as $r)
                <tr>
                    <td class="p-2 text-slate-400">{{ $r['riga'] }}</td>
                    <td class="p-2 font-bold">{{ $r['camera'] }}</td>
                    <td class="p-2">{{ $r['arrivo'] ? \Carbon\Carbon::parse($r['arrivo'])->format('d/m/Y') : '—' }}</td>
                    <td class="p-2">{{ $r['partenza'] ? \Carbon\Carbon::parse($r['partenza'])->format('d/m/Y') : '—' }}</td>
                    <td class="p-2 font-mono text-xs">{{ $r['riferimento'] }}</td>
                    <td class="p-2">{{ $r['pax'] }}</td>
                    <td class="p-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $stili[$r['esito']][1] }}">{{ $stili[$r['esito']][0] }}</span>
                        @if ($r['errori'])<span class="text-red-700 text-xs">{{ implode('; ', $r['errori']) }}</span>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if ($daSalvare)
    <form method="POST" action="{{ route('reception.prenotazioni.conferma') }}" class="flex items-center gap-3">
        @csrf
        <button class="rounded-lg bg-slate-900 text-white px-5 py-2 font-semibold">✓ Importa {{ $daSalvare }} prenotazioni</button>
        <a href="{{ route('reception.prenotazioni') }}" class="underline text-slate-600">Annulla</a>
    </form>
    @if ($conteggi['scartata'] ?? 0)
        <p class="mt-2 text-sm text-slate-600">Le righe scartate non vengono importate. Se manca una camera, aggiungila nella pagina <a href="{{ route('reception.camere') }}" class="underline">Camere</a> e ricarica il file.</p>
    @endif
@else
    <p class="text-slate-600">Niente da importare: tutte le righe sono già presenti o scartate.</p>
@endif
@endsection
