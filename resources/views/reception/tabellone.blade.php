@extends('layouts.app')
@section('titolo', 'Tabellone')

@section('content')
@php
    $config = [
        'dati' => $dati,
        'urls' => [
            'dati' => route('reception.dati'),
            'stato' => route('reception.camera.stato', '__ID__'),
            'camera' => route('reception.camera.aggiorna', '__ID__'),
            'segnalazione' => route('reception.segnalazione', '__ID__'),
        ],
    ];
    $statoNomi = ['da_fare' => 'Da fare', 'in_pulizia' => 'In pulizia', 'pronta' => 'Pronta'];
@endphp
<script type="application/json" id="tabellone-config">@json($config)</script>

<div x-data="tabellone" x-cloak>
    {{-- Avvisi suoni / connessione --}}
    <template x-if="!suoniAttivi">
        <button type="button" @click="attivaSuoni()" class="w-full mb-3 rounded-xl bg-amber-100 border-2 border-amber-300 text-amber-900 px-4 py-3 font-semibold text-left">
            🔔 Clicca qui per attivare suoni e notifiche (camere pronte e segnalazioni)
        </button>
    </template>
    <template x-if="suoniAttivi && audioBloccato">
        <div class="mb-3 rounded-xl bg-amber-100 text-amber-900 px-4 py-2 text-sm">🔇 Clicca un punto qualsiasi della pagina per riattivare i suoni.</div>
    </template>
    <template x-if="!connessione">
        <div class="mb-3 rounded-xl bg-red-100 text-red-900 px-4 py-2 font-semibold">⚠️ Connessione assente: il tabellone non si sta aggiornando.</div>
    </template>

    {{-- Contatori --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="rounded-xl bg-slate-200 p-3"><div class="text-3xl font-black" x-text="conta('da_fare')"></div><div class="text-sm font-semibold">Da fare</div></div>
        <div class="rounded-xl bg-yellow-300 p-3"><div class="text-3xl font-black" x-text="conta('in_pulizia')"></div><div class="text-sm font-semibold">In pulizia</div></div>
        <div class="rounded-xl bg-green-600 text-white p-3"><div class="text-3xl font-black" x-text="conta('pronta')"></div><div class="text-sm font-semibold">Pronte</div></div>
        <div class="rounded-xl p-3" :class="aperte ? 'bg-red-600 text-white' : 'bg-white'"><div class="text-3xl font-black" x-text="segnalazioni.length"></div><div class="text-sm font-semibold">Segnalazioni aperte</div></div>
    </div>

    {{-- Filtri --}}
    <div class="flex flex-wrap items-center gap-2 mb-4 text-sm">
        <select x-model="filtroPiano" class="border rounded-lg px-3 py-2 bg-white">
            <option value="">Tutti i piani</option>
            <template x-for="p in piani" :key="p"><option :value="p" x-text="'Piano ' + p"></option></template>
        </select>
        <select x-model="filtroAddetta" class="border rounded-lg px-3 py-2 bg-white">
            <option value="">Tutte le addette</option>
            <template x-for="a in addette" :key="a.id"><option :value="a.id" x-text="a.nome"></option></template>
            <option value="nessuna">Non assegnate</option>
        </select>
        <span class="ml-auto text-slate-500">Aggiornato alle <span x-text="ultimoAggiornamento"></span></span>
        <template x-if="suoniAttivi">
            <button type="button" @click="disattivaSuoni()" class="rounded-lg border bg-white px-3 py-2">🔔 Suoni attivi</button>
        </template>
    </div>

    <div class="grid lg:grid-cols-[1fr_22rem] gap-6">
        {{-- Camere --}}
        <div>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(8.5rem,1fr))] gap-3 content-start">
                <template x-for="c in visibili" :key="c.id">
                    <button type="button" @click="apri(c)"
                            class="relative rounded-xl p-3 text-center min-h-28 transition-colors"
                            :class="{
                                'bg-slate-200': c.stato === 'da_fare',
                                'bg-yellow-300': c.stato === 'in_pulizia',
                                'bg-green-600 text-white': c.stato === 'pronta',
                                'ring-4 ring-red-600': c.urgente,
                            }">
                        <div class="text-3xl font-black tabular-nums" x-text="c.numero"></div>
                        <div class="text-sm font-semibold" x-text="{ da_fare: 'Da fare', in_pulizia: 'In pulizia', pronta: 'Pronta' }[c.stato]"></div>
                        <div class="text-xs opacity-80 truncate" x-text="(c.tipo === 'fermata' ? '🛏 ' : c.tipo === 'extra' ? '★ ' : '🧳 ') + (c.staff ?? 'non assegnata')"></div>
                        <div class="text-xs opacity-80" x-show="c.stato === 'in_pulizia' && c.iniziata" x-text="'dalle ' + c.iniziata"></div>
                        <div class="text-xs opacity-80" x-show="c.stato === 'pronta' && c.finita" x-text="'alle ' + c.finita"></div>
                        <span x-show="c.urgente" class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-red-600 text-white text-[10px] font-bold px-2 uppercase">urgente</span>
                        <span x-show="c.nota" class="absolute top-1 right-1 text-sm">📝</span>
                        <span x-show="c.segnalazioni > 0" class="absolute bottom-1 right-1 size-6 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center" x-text="c.segnalazioni"></span>
                    </button>
                </template>
            </div>
            <template x-if="camere.length === 0">
                <p class="text-slate-500">Nessuna camera preparata per oggi. Vai su <a href="{{ route('reception.giornata') }}" class="underline">Preparazione del giorno</a>.</p>
            </template>
        </div>

        {{-- Segnalazioni --}}
        <aside>
            <h2 class="font-bold mb-2">Segnalazioni aperte</h2>
            <template x-if="segnalazioni.length === 0"><p class="text-slate-500 text-sm">Nessuna segnalazione aperta. 👍</p></template>
            <div class="space-y-3">
                <template x-for="s in segnalazioni" :key="s.id">
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4" :class="s.stato === 'aperta' ? 'border-red-600' : 'border-amber-400'">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="font-bold text-lg" x-text="'Camera ' + s.numero"></span>
                            <span class="text-xs text-slate-500" x-text="s.quando"></span>
                        </div>
                        <div class="text-sm text-slate-600" x-text="s.staff ?? ''"></div>
                        <template x-if="s.foto">
                            <img :src="s.foto" alt="Foto della segnalazione" loading="lazy" @click="fotoGrande = s.foto"
                                 class="mt-2 rounded-lg w-full h-36 object-cover cursor-zoom-in bg-slate-100">
                        </template>
                        <p class="mt-2 text-sm" x-show="s.nota" x-text="s.nota"></p>
                        <div class="mt-2 flex gap-2">
                            <template x-if="s.stato === 'aperta'">
                                <button type="button" @click="segnalazione(s, 'presa_in_carico')" class="flex-1 rounded-lg border px-2 py-1.5 text-sm font-semibold">👀 Presa in carico</button>
                            </template>
                            <template x-if="s.stato === 'presa_in_carico'">
                                <span class="flex-1 text-sm text-amber-700 font-semibold self-center">In carico</span>
                            </template>
                            <button type="button" @click="segnalazione(s, 'risolta')" class="flex-1 rounded-lg bg-green-600 text-white px-2 py-1.5 text-sm font-semibold">✓ Risolta</button>
                        </div>
                    </div>
                </template>
            </div>
        </aside>
    </div>

    {{-- Pannello camera --}}
    <div x-show="aperta" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/60 flex items-center justify-center p-4" @click.self="aperta = null" @keydown.escape.window="aperta = null">
        <template x-if="aperta">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-4xl font-black tabular-nums" x-text="aperta.numero"></div>
                        <div class="text-sm text-slate-500" x-text="'Piano ' + aperta.piano + ' · ' + ({ partenza: 'Cambio', fermata: 'Riassetto', extra: 'Extra' })[aperta.tipo] + (aperta.note_fisse ? ' · ' + aperta.note_fisse : '')"></div>
                    </div>
                    <button type="button" @click="aperta = null" class="size-10 rounded-full bg-slate-100 text-xl" aria-label="Chiudi">✕</button>
                </div>

                <div>
                    <p class="text-sm font-semibold mb-1">Stato</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['da_fare' => 'bg-slate-200', 'in_pulizia' => 'bg-yellow-300', 'pronta' => 'bg-green-600 text-white'] as $s => $classi)
                            <button type="button" @click="cambiaStato(aperta, '{{ $s }}')" class="rounded-lg py-3 font-semibold {{ $classi }}"
                                    :class="aperta.stato === '{{ $s }}' ? 'ring-4 ring-slate-900' : 'opacity-60'">{{ $statoNomi[$s] }}</button>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        <span x-show="aperta.iniziata" x-text="'Iniziata alle ' + aperta.iniziata"></span>
                        <span x-show="aperta.finita" x-text="' · finita alle ' + aperta.finita"></span>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-sm font-semibold w-20">Addetta</label>
                    <select class="flex-1 border rounded-lg px-3 py-2" @change="riassegna(aperta, $event.target.value)">
                        <option value="" :selected="aperta.staff_id === null">— nessuna —</option>
                        <template x-for="a in addette" :key="a.id"><option :value="a.id" :selected="aperta.staff_id === a.id" x-text="a.nome"></option></template>
                    </select>
                </div>

                <button type="button" @click="cambiaUrgente(aperta)" class="w-full rounded-lg py-2 font-semibold border-2"
                        :class="aperta.urgente ? 'bg-red-600 border-red-600 text-white' : 'border-red-600 text-red-700'"
                        x-text="aperta.urgente ? '🔥 Urgente (clicca per togliere)' : '🔥 Segna come urgente'"></button>

                <div>
                    <label class="text-sm font-semibold">Nota per l'addetta <span class="font-normal text-slate-500">(la vede sul telefono con 📝)</span></label>
                    <textarea x-model="notaBozza" rows="2" maxlength="500" class="w-full border rounded-lg px-3 py-2 mt-1" placeholder="es. Culla da togliere"></textarea>
                    <button type="button" @click="salvaNota(aperta)" x-show="notaBozza !== (aperta.nota ?? '')" class="mt-1 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Salva nota</button>
                </div>

                <template x-if="segnalazioniDi(aperta).length">
                    <p class="text-sm text-red-700 font-semibold" x-text="segnalazioniDi(aperta).length + ' segnalazione/i aperta/e: vedi colonna a destra'"></p>
                </template>
            </div>
        </template>
    </div>

    {{-- Foto ingrandita --}}
    <div x-show="fotoGrande" x-transition.opacity class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" @click="fotoGrande = null">
        <img :src="fotoGrande" alt="Foto" class="max-h-full max-w-full rounded-lg">
    </div>
</div>
@endsection
