@extends('layouts.app')
@section('titolo', 'Camere')

@section('content')
<div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
    <h1 class="text-2xl font-bold">Camere</h1>
    <span class="text-slate-500">{{ $totale }} camere attive</span>
</div>

<div class="grid md:grid-cols-2 gap-4 mb-6">
    <form method="POST" action="{{ route('reception.camere.importa') }}" class="bg-white rounded-xl shadow-sm p-4">
        @csrf
        <h2 class="font-semibold mb-3">Importazione rapida</h2>
        <div class="flex flex-wrap items-end gap-2">
            <label class="text-sm">Da<br><input name="da" type="number" required value="{{ old('da') }}" placeholder="101" class="w-24 border rounded-lg px-2 py-2"></label>
            <label class="text-sm">a<br><input name="a" type="number" required value="{{ old('a') }}" placeholder="120" class="w-24 border rounded-lg px-2 py-2"></label>
            <label class="text-sm">piano<br><input name="piano" type="number" required value="{{ old('piano', 1) }}" class="w-20 border rounded-lg px-2 py-2"></label>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">Aggiungi</button>
        </div>
        <p class="text-xs text-slate-500 mt-2">Esempio: da 101 a 120, piano 1 → crea le camere 101, 102 … 120. Quelle che esistono già non vengono toccate.</p>
    </form>

    <form method="POST" action="{{ route('reception.camere.store') }}" class="bg-white rounded-xl shadow-sm p-4">
        @csrf
        <h2 class="font-semibold mb-3">Una camera</h2>
        <div class="flex flex-wrap items-end gap-2">
            <label class="text-sm">Numero<br><input name="numero" required maxlength="20" placeholder="es. 12B" class="w-24 border rounded-lg px-2 py-2"></label>
            <label class="text-sm">piano<br><input name="piano" type="number" required value="1" class="w-20 border rounded-lg px-2 py-2"></label>
            <label class="text-sm flex-1 min-w-40">Nota fissa (facoltativa)<br><input name="note_fisse" maxlength="255" placeholder="es. letto a castello" class="w-full border rounded-lg px-2 py-2"></label>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">Aggiungi</button>
        </div>
    </form>
</div>

@forelse ($perPiano as $piano => $camere)
    <section class="mb-6">
        <h2 class="font-semibold text-lg mb-2">Piano {{ $piano }} <span class="text-slate-400 font-normal text-sm">({{ $camere->count() }})</span></h2>
        <div class="bg-white rounded-xl shadow-sm divide-y">
            @foreach ($camere as $c)
                <form method="POST" action="{{ route('reception.camere.update', $c) }}"
                      @class(['flex flex-wrap items-center gap-2 px-3 py-2', 'opacity-50' => ! $c->attiva])
                      x-data="{ cambiata: false }" @input="cambiata = true">
                    @csrf @method('PUT')
                    <input name="numero" value="{{ $c->numero }}" required maxlength="20" class="w-20 border rounded-lg px-2 py-1.5 font-bold text-lg" aria-label="Numero">
                    <label class="text-sm text-slate-500">piano <input name="piano" type="number" value="{{ $c->piano }}" required class="w-16 border rounded-lg px-2 py-1.5 text-slate-900"></label>
                    <input name="note_fisse" value="{{ $c->note_fisse }}" maxlength="255" placeholder="Nota fissa" class="flex-1 min-w-40 border rounded-lg px-2 py-1.5" aria-label="Nota fissa">
                    <label class="text-sm flex items-center gap-1"><input type="checkbox" name="attiva" value="1" @checked($c->attiva)> attiva</label>
                    <button x-show="cambiata" x-cloak class="rounded-lg bg-slate-900 text-white px-3 py-1.5 text-sm font-semibold">Salva</button>
                </form>
            @endforeach
        </div>
    </section>
@empty
    <p class="text-slate-500">Nessuna camera. Usa l'importazione rapida qui sopra.</p>
@endforelse
@endsection
