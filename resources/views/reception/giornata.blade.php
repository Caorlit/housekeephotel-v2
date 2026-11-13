@extends('layouts.app')
@section('titolo', 'Preparazione del giorno')

@section('content')
@php
    $urls = [
        'applica' => route('reception.giornata.applica', $iniziale['data']),
        'incolla' => route('reception.giornata.incolla', $iniziale['data']),
        'distribuisci' => route('reception.giornata.distribuisci', $iniziale['data']),
        'copiaIeri' => route('reception.giornata.copia-ieri', $iniziale['data']),
        'sostituisci' => route('reception.giornata.sostituisci', $iniziale['data']),
        'pagina' => route('reception.giornata'),
    ];
    $giorno = \Carbon\Carbon::parse($iniziale['data'])->locale('it');
@endphp
<script type="application/json" id="giornata-config">@json(['stato' => $iniziale, 'urls' => $urls, 'oggi' => $oggi])</script>

<div x-data="giornata" x-cloak>
    {{-- Data --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <h1 class="text-2xl font-bold mr-2">Preparazione del giorno</h1>
        <a href="{{ route('reception.giornata', ['data' => $giorno->copy()->subDay()->toDateString()]) }}" class="rounded-lg border bg-white px-3 py-2" aria-label="Giorno prima">◀</a>
        <input type="date" value="{{ $iniziale['data'] }}" @change="vaiAl($event.target.value)" class="border rounded-lg px-3 py-2 bg-white">
        <a href="{{ route('reception.giornata', ['data' => $giorno->copy()->addDay()->toDateString()]) }}" class="rounded-lg border bg-white px-3 py-2" aria-label="Giorno dopo">▶</a>
        <span class="text-slate-600 first-letter:uppercase">{{ $giorno->translatedFormat('l j F') }}</span>
        @if ($iniziale['data'] === $oggi)<span class="rounded-full bg-slate-900 text-white text-xs px-2 py-0.5">oggi</span>@endif
        @if ($iniziale['data'] !== $oggi)<a href="{{ route('reception.giornata') }}" class="text-sm underline">torna a oggi</a>@endif
    </div>

    {{-- Messaggi --}}
    <template x-if="messaggio"><div class="mb-3 rounded-lg bg-green-100 text-green-900 px-4 py-2" x-text="messaggio"></div></template>
    <template x-for="a in avvisi"><div class="mb-3 rounded-lg bg-amber-100 text-amber-900 px-4 py-2" x-text="a"></div></template>

    <div class="grid lg:grid-cols-[1fr_20rem] gap-4">
        <div>
            {{-- Pennelli --}}
            <div class="sticky top-0 z-10 bg-slate-100 pb-3">
                <p class="text-sm text-slate-600 mb-2">1. Scegli cosa fare &nbsp;·&nbsp; 2. Tocca le camere (o "tutto il piano")</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="a in addette" :key="a.id">
                        <button type="button" @click="strumento = { tipo: 'assegna', staff_id: a.id }"
                                class="flex items-center gap-2 rounded-full border-2 bg-white px-3 py-2 font-semibold"
                                :style="eAttivo('assegna', a.id) ? `border-color:${a.colore}; background:${a.colore}; color:#fff` : `border-color:${a.colore}`">
                            <span class="size-3 rounded-full" :style="`background:${eAttivo('assegna', a.id) ? '#fff' : a.colore}`"></span>
                            <span x-text="a.nome"></span>
                            <span class="text-xs rounded-full px-1.5" :class="eAttivo('assegna', a.id) ? 'bg-white/25' : 'bg-slate-100'" x-text="conta(a.id)"></span>
                        </button>
                    </template>
                    <button type="button" @click="strumento = { tipo: 'assegna', staff_id: null }"
                            class="rounded-full border-2 border-slate-400 px-3 py-2 font-semibold"
                            :class="eAttivo('assegna', null) ? 'bg-slate-500 text-white' : 'bg-white'">
                        Nessuna <span class="text-xs" x-text="'(' + conta(null) + ')'"></span>
                    </button>
                    <span class="w-px bg-slate-300 mx-1"></span>
                    <template x-for="t in tipi" :key="t.valore">
                        <button type="button" @click="strumento = { tipo: 'tipo', valore: t.valore }"
                                class="rounded-full border-2 border-slate-700 px-3 py-2 font-semibold"
                                :class="eAttivo('tipo', t.valore) ? 'bg-slate-700 text-white' : 'bg-white'"
                                x-text="t.icona + ' ' + t.nome"></button>
                    </template>
                    <button type="button" @click="strumento = { tipo: 'togli' }"
                            class="rounded-full border-2 border-red-600 px-3 py-2 font-semibold"
                            :class="eAttivo('togli') ? 'bg-red-600 text-white' : 'bg-white text-red-700'">✕ Togli dal giorno</button>
                </div>
            </div>

            {{-- Camere per piano --}}
            <template x-for="piano in piani" :key="piano.numero">
                <section class="mb-5">
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="font-semibold">Piano <span x-text="piano.numero"></span></h2>
                        <button type="button" @click="applicaA(piano.camere)" class="text-sm underline text-slate-600">applica a tutto il piano</button>
                    </div>
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(5.5rem,1fr))] gap-2">
                        <template x-for="c in piano.camere" :key="c.id">
                            <button type="button" @click="tocca(c)"
                                    class="relative h-20 rounded-xl border-2 flex flex-col items-center justify-center transition-colors"
                                    :class="c.giorno ? 'shadow-sm' : 'border-dashed border-slate-300 bg-transparent text-slate-400'"
                                    :style="stile(c)">
                                <span class="text-2xl font-black tabular-nums" x-text="c.numero"></span>
                                <template x-if="c.giorno">
                                    <span class="text-xs font-semibold truncate max-w-full px-1" x-text="iconaTipo(c.giorno.tipo) + ' ' + nomeAddetta(c.giorno.staff_id)"></span>
                                </template>
                                <template x-if="c.giorno && c.giorno.stato !== 'da_fare'">
                                    <span class="absolute top-1 right-1 size-3 rounded-full ring-2 ring-white" :class="c.giorno.stato === 'pronta' ? 'bg-green-600' : 'bg-yellow-400'"></span>
                                </template>
                                <template x-if="c.giorno && c.giorno.urgente">
                                    <span class="absolute top-1 left-1 text-xs font-bold text-red-600">!</span>
                                </template>
                            </button>
                        </template>
                    </div>
                </section>
            </template>
            <template x-if="camere.length === 0">
                <p class="text-slate-500">Nessuna camera in anagrafica. Aggiungile nella pagina <a href="{{ route('reception.camere') }}" class="underline">Camere</a>.</p>
            </template>
        </div>

        {{-- Colonna laterale --}}
        <aside class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="font-semibold">Riepilogo</p>
                <p class="text-3xl font-black" x-text="nelGiorno"></p>
                <p class="text-sm text-slate-600">camere da pulire · <span x-text="conta(null)"></span> non assegnate</p>
                <p class="text-sm text-slate-600"><span x-text="contaTipo('partenza')"></span> cambi · <span x-text="contaTipo('fermata')"></span> riassetti</p>
            </div>

            <form class="bg-white rounded-xl shadow-sm p-4 space-y-2" @submit.prevent="incolla()">
                <p class="font-semibold">Inserimento rapido</p>
                <textarea x-model="incollaTesto" rows="3" placeholder="Incolla i numeri: 101, 104 108..." class="w-full border rounded-lg px-3 py-2"></textarea>
                <div class="flex gap-2">
                    <select x-model="incollaTipo" class="border rounded-lg px-2 py-2 flex-1">
                        <template x-for="t in tipi"><option :value="t.valore" x-text="t.icona + ' ' + t.nome"></option></template>
                    </select>
                    <select x-model="incollaAddetta" class="border rounded-lg px-2 py-2 flex-1">
                        <option value="">— a nessuna —</option>
                        <template x-for="a in addette"><option :value="a.id" x-text="a.nome"></option></template>
                    </select>
                </div>
                <button class="w-full rounded-lg bg-slate-900 text-white py-2 font-semibold" :disabled="occupato">Inserisci</button>
            </form>

            <div class="bg-white rounded-xl shadow-sm p-4 space-y-2">
                <p class="font-semibold">Distribuisci automaticamente</p>
                <p class="text-xs text-slate-500">Divide le camere non assegnate in modo equo (contando quelle che ognuna ha già), a blocchi per piano.</p>
                <template x-for="a in addette" :key="'d' + a.id">
                    <label class="flex items-center gap-2"><input type="checkbox" :value="a.id" x-model.number="distribuisciA"> <span class="size-3 rounded-full" :style="`background:${a.colore}`"></span> <span x-text="a.nome"></span></label>
                </template>
                <button type="button" @click="distribuisci()" class="w-full rounded-lg border-2 border-slate-900 py-2 font-semibold" :disabled="occupato || !distribuisciA.length">Distribuisci</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 space-y-2">
                <p class="font-semibold">Sostituisci (giorno libero)</p>
                <div class="flex items-center gap-2">
                    <select x-model="sostDa" class="border rounded-lg px-2 py-2 flex-1" aria-label="Addetta assente">
                        <option value="">Chi manca?</option>
                        <template x-for="a in addette" :key="'sd' + a.id"><option :value="a.id" x-text="a.nome"></option></template>
                    </select>
                    <span>→</span>
                    <select x-model="sostA" class="border rounded-lg px-2 py-2 flex-1" aria-label="Sostituta">
                        <option value="">Chi la sostituisce?</option>
                        <template x-for="a in addette" :key="'sa' + a.id"><option :value="a.id" x-text="a.nome"></option></template>
                    </select>
                </div>
                <button type="button" @click="sostituisci()" class="w-full rounded-lg border-2 border-slate-900 py-2 font-semibold" :disabled="occupato || !sostDa || !sostA || sostDa === sostA">Sposta le sue camere</button>
                <p class="text-xs text-slate-500">Solo per questo giorno: dal giorno dopo le camere tornano in automatico alla titolare.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4">
                <button type="button" @click="copiaIeri()" class="w-full rounded-lg border py-2 font-semibold" :disabled="occupato">⧉ Copia da ieri</button>
                <p class="text-xs text-slate-500 mt-2">Riprende camere, tipo e addette del giorno prima. Le camere già inserite oggi non vengono toccate.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
