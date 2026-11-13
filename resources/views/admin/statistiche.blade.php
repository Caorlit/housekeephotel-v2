@extends('layouts.app')
@section('titolo', 'Statistiche')

@section('content')
<h1 class="text-2xl font-bold mb-4">Statistiche</h1>

<form method="GET" class="flex flex-wrap items-end gap-2 mb-6">
    <label class="text-sm">Hotel<br>
        <select name="hotel" class="border rounded-lg px-3 py-2 bg-white">
            @foreach ($hotels as $h)<option value="{{ $h->id }}" @selected($hotel?->id === $h->id)>{{ $h->nome }}</option>@endforeach
        </select></label>
    <label class="text-sm">Dal<br><input type="date" name="dal" value="{{ $dal }}" class="border rounded-lg px-3 py-2 bg-white"></label>
    <label class="text-sm">Al<br><input type="date" name="al" value="{{ $al }}" class="border rounded-lg px-3 py-2 bg-white"></label>
    <button class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">Mostra</button>
</form>

@if (! $s)
    <p class="text-slate-500">Nessun hotel.</p>
@else
    @php $min = fn ($v) => $v === null ? '—' : $v.' min'; @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-3xl font-black">{{ $s['pulite'] }}</div><div class="text-sm text-slate-600">camere pulite <span class="text-slate-400">su {{ $s['camere_previste'] }} previste</span></div></div>
        <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-3xl font-black">{{ $min($s['media_cambio']) }}</div><div class="text-sm text-slate-600">🧳 tempo medio Cambio <span class="text-slate-400">({{ $s['cambi'] }})</span></div></div>
        <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-3xl font-black">{{ $min($s['media_riassetto']) }}</div><div class="text-sm text-slate-600">🛏 tempo medio Riassetto <span class="text-slate-400">({{ $s['riassetti'] }})</span></div></div>
        <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-3xl font-black">{{ $s['segnalazioni'] }}</div><div class="text-sm text-slate-600">segnalazioni · {{ $s['segnalazioni_risolte'] }} risolte{{ $s['ore_risoluzione'] !== null ? ' in media in '.$s['ore_risoluzione'].' h' : '' }}</div></div>
    </div>

    <h2 class="font-semibold mb-2">Per addetta</h2>
    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 border-b">
                <tr><th class="p-3">Addetta</th><th class="p-3 text-right">Pulite</th><th class="p-3 text-right">Cambi</th><th class="p-3 text-right">Riassetti</th><th class="p-3 text-right">Media cambio</th><th class="p-3 text-right">Media riassetto</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($s['per_addetta'] as $a)
                    <tr>
                        <td class="p-3 font-semibold">{{ $a['nome'] }}</td>
                        <td class="p-3 text-right">{{ $a['pulite'] }}</td>
                        <td class="p-3 text-right">{{ $a['cambi'] }}</td>
                        <td class="p-3 text-right">{{ $a['riassetti'] }}</td>
                        <td class="p-3 text-right">{{ $min($a['media_cambio']) }}</td>
                        <td class="p-3 text-right">{{ $min($a['media_riassetto']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-slate-500">Nessuna camera pulita nel periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-slate-500">Il tempo è quello tra il primo tocco (giallo) e il secondo (verde). Le durate sotto {{ \App\Services\Statistiche::MIN_MINUTI }} minuto o sopra {{ \App\Services\Statistiche::MAX_MINUTI / 60 }} ore sono escluse dalle medie (di solito sono tocchi dimenticati).</p>
@endif
@endsection
