@extends('layouts.app')
@section('titolo', 'Prenotazioni')

@section('content')
@php
    $config = [
        'dal' => $dal,
        'oggi' => $oggi,
        'giorni' => \App\Http\Controllers\Reception\PrenotazioniController::GIORNI,
        'camere' => $camere,
        'soggiorni' => $soggiorni,
        'frequenze' => collect($frequenze)->map(fn ($nome, $valore) => ['valore' => $valore, 'nome' => $nome])->values(),
        'urls' => [
            'pagina' => route('reception.prenotazioni'),
            'crea' => route('reception.prenotazioni.store'),
            'aggiorna' => route('reception.prenotazioni.update', '__ID__'),
        ],
    ];
@endphp
<script type="application/json" id="planning-config">@json($config)</script>

<div x-data="planning" x-cloak>
    <div class="flex flex-wrap items-center gap-2 mb-3">
        <h1 class="text-2xl font-bold mr-2">Prenotazioni</h1>
        <button type="button" @click="vai(-7)" class="rounded-lg border bg-white px-3 py-2">◀ 7 giorni</button>
        <a href="{{ route('reception.prenotazioni') }}" class="rounded-lg border bg-white px-3 py-2">Oggi</a>
        <button type="button" @click="vai(7)" class="rounded-lg border bg-white px-3 py-2">7 giorni ▶</button>
        @if (Route::has('reception.prenotazioni.importa'))
            <a href="{{ route('reception.prenotazioni.importa') }}" class="ml-auto rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">⬆ Importa da file CSV</a>
        @endif
    </div>
    <p class="text-sm text-slate-600 mb-3">Tocca una casella vuota per inserire una prenotazione, o una barra per modificarla. 🧳 = Cambio (giorno di partenza) · 🛏 = Riassetto. Le camere da pulire compaiono da sole nella Preparazione del giorno.</p>

    <div class="overflow-x-auto bg-white rounded-xl shadow-sm">
        <div class="min-w-[56rem]">
            {{-- Intestazione giorni --}}
            <div class="grid grid-cols-[4.5rem_repeat(14,minmax(0,1fr))] border-b sticky top-0 bg-white z-10">
                <div class="p-2 text-xs text-slate-500">Camera</div>
                <template x-for="c in colonne" :key="c.data">
                    <div class="p-1 text-center text-xs border-l" :class="{ 'bg-slate-50': c.festivo, 'bg-amber-100 font-bold': c.oggi }">
                        <div x-text="c.giorno"></div>
                        <div class="text-base font-semibold" x-text="c.numero"></div>
                        <div class="text-slate-400" x-text="c.mese"></div>
                    </div>
                </template>
            </div>

            {{-- Righe camere --}}
            <template x-for="camera in camere" :key="camera.id">
                <div class="grid grid-cols-[4.5rem_1fr] border-b last:border-b-0">
                    <div class="p-2 font-bold tabular-nums flex items-center" x-text="camera.numero"></div>
                    <div class="relative h-14">
                        {{-- Caselle cliccabili + icone pulizia --}}
                        <div class="absolute inset-0 grid grid-cols-14">
                            <template x-for="c in colonne" :key="c.data">
                                <button type="button" @click="nuovo(camera, c.data)" class="border-l h-full flex items-end justify-center pb-0.5 text-sm hover:bg-slate-100"
                                        :class="{ 'bg-slate-50': c.festivo, 'bg-amber-50': c.oggi }" :aria-label="'Nuova prenotazione ' + camera.numero + ' ' + c.data">
                                    <span x-text="icona(camera.id, c.data)"></span>
                                </button>
                            </template>
                        </div>
                        {{-- Barre dei soggiorni --}}
                        <div class="absolute inset-x-0 top-1 h-7 grid grid-cols-28 pointer-events-none">
                            <template x-for="s in soggiorniDi(camera.id)" :key="s.id">
                                <button type="button" @click="modifica(s)" :style="barra(s)"
                                        class="pointer-events-auto rounded-md text-white text-[11px] font-semibold px-1.5 truncate text-left shadow-sm mx-px"
                                        :title="etichetta(s)" x-text="etichetta(s)"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <template x-if="camere.length === 0">
        <p class="mt-4 text-slate-500">Nessuna camera: aggiungile prima nella pagina <a href="{{ route('reception.camere') }}" class="underline">Camere</a>.</p>
    </template>

    {{-- Modulo prenotazione --}}
    <div x-show="modulo" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/60 flex items-center justify-center p-4" @click.self="modulo = null" @keydown.escape.window="modulo = null">
        <template x-if="modulo">
            <form class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 space-y-3" @submit.prevent="salva()">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold" x-text="modulo.id ? 'Modifica prenotazione' : 'Nuova prenotazione'"></h2>
                    <button type="button" @click="modulo = null" class="size-9 rounded-full bg-slate-100" aria-label="Chiudi">✕</button>
                </div>
                <template x-if="errore"><div class="rounded-lg bg-red-100 text-red-900 px-3 py-2 text-sm" x-text="errore"></div></template>

                <label class="block text-sm font-semibold">Camera
                    <select x-model.number="modulo.room_id" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal">
                        <template x-for="c in camere" :key="'m' + c.id"><option :value="c.id" x-text="c.numero" :selected="c.id === modulo.room_id"></option></template>
                    </select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm font-semibold">Arrivo<input type="date" x-model="modulo.arrivo" required class="mt-1 w-full border rounded-lg px-3 py-2 font-normal"></label>
                    <label class="block text-sm font-semibold">Partenza<input type="date" x-model="modulo.partenza" required class="mt-1 w-full border rounded-lg px-3 py-2 font-normal"></label>
                </div>
                <p class="text-xs text-slate-500" x-text="notti() + (notti() === 1 ? ' notte' : ' notti')"></p>
                <label class="block text-sm font-semibold">Riassetto
                    <select x-model.number="modulo.riassetto_ogni" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal">
                        <template x-for="f in frequenze" :key="f.valore"><option :value="f.valore" x-text="f.nome" :selected="Number(f.valore) === Number(modulo.riassetto_ogni)"></option></template>
                    </select>
                </label>
                <div class="grid grid-cols-[1fr_6rem] gap-3">
                    <label class="block text-sm font-semibold">Riferimento <span class="font-normal text-slate-400">(facoltativo)</span><input x-model="modulo.riferimento" maxlength="100" placeholder="es. codice prenotazione" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal"></label>
                    <label class="block text-sm font-semibold">Persone<input type="number" min="1" max="30" x-model="modulo.pax" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal"></label>
                </div>
                <label class="block text-sm font-semibold">Nota <span class="font-normal text-slate-400">(facoltativa)</span><input x-model="modulo.nota" maxlength="255" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal"></label>

                <div class="flex gap-2 pt-2">
                    <template x-if="modulo.id">
                        <button type="button" @click="cancella()" class="rounded-lg border border-red-300 text-red-700 px-4 py-2 font-semibold" :disabled="salvando">Cancella</button>
                    </template>
                    <button class="ml-auto rounded-lg bg-slate-900 text-white px-5 py-2 font-semibold" :disabled="salvando" x-text="salvando ? 'Salvo…' : 'Salva'"></button>
                </div>
            </form>
        </template>
    </div>
</div>
@endsection
