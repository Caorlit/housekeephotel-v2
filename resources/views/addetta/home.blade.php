@extends('layouts.addetta')

@section('content')
@php
    $config = [
        'staffId' => $staff->id,
        'urls' => [
            'camere' => route('addetta.camere', $staff->token_accesso, false),
            'azioni' => route('addetta.azioni', $staff->token_accesso, false),
            'segnalazioni' => route('addetta.segnalazione', $staff->token_accesso, false),
        ],
        'dati' => $dati,
        't' => $testi,
    ];
@endphp
<script type="application/json" id="hk-config">@json($config)</script>

<div x-data="addetta" x-cloak class="min-h-dvh pb-40">

    {{-- Intestazione: nome, data, stato della rete --}}
    <header class="sticky top-0 z-20 bg-slate-900 text-white px-4 pt-[max(env(safe-area-inset-top),0.75rem)] pb-3 flex items-center justify-between gap-3 shadow">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold truncate">{{ __('addetta.ciao', ['nome' => $staff->nome]) }}</h1>
            <p class="text-base text-slate-300 first-letter:uppercase">{{ $oggi->translatedFormat('l j F') }}</p>
        </div>
        <div class="shrink-0 flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold"
             :class="online ? 'bg-slate-800' : 'bg-orange-500 text-slate-900'"
             :aria-label="online ? '' : t.senza_rete">
            <span class="inline-block size-3 rounded-full" :class="online ? 'bg-green-400' : 'bg-slate-900'"></span>
            <template x-if="!online"><span x-text="t.senza_rete"></span></template>
            <template x-if="inAttesa > 0">
                <span class="flex items-center gap-1">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9"/></svg>
                    <span x-text="inAttesa"></span>
                </span>
            </template>
        </div>
    </header>

    {{-- Nessuna camera --}}
    <template x-if="camere.length === 0">
        <div class="flex flex-col items-center justify-center text-center px-6 pt-24 text-slate-500">
            <svg class="size-24 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 18v-6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v6M3 18h18M3 18v2m18-2v2M6 9V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3"/></svg>
            <p class="text-2xl font-semibold" x-text="t.nessuna_camera"></p>
        </div>
    </template>

    {{-- Le camere --}}
    <main class="grid grid-cols-2 gap-3 p-3">
        <template x-for="c in camere" :key="c.id">
            <button type="button"
                    @click="tocca(c)"
                    class="tessera relative min-h-40 rounded-2xl px-3 pt-11 pb-3 flex flex-col items-center justify-center shadow-sm transition-colors duration-150 active:scale-[0.97]"
                    :class="{
                        'bg-slate-200 text-slate-900': c.stato === 'da_fare',
                        'bg-yellow-300 text-slate-900': c.stato === 'in_pulizia',
                        'bg-green-600 text-white': c.stato === 'pronta',
                        'ring-[6px] ring-red-600 ring-offset-2 ring-offset-slate-50': c.urgente,
                    }"
                    :aria-label="c.numero + ' – ' + t[c.stato]">

                {{-- Tipo: partenza o fermata --}}
                <span class="absolute top-2 left-2 size-9 flex items-center justify-center rounded-full bg-white/60 text-slate-800" :title="t[c.tipo]">
                    <template x-if="c.tipo === 'partenza'">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="7" width="14" height="13" rx="2"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M9 20v1m6-1v1"/></svg>
                    </template>
                    <template x-if="c.tipo === 'fermata'">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v6M3 18h18M3 18v2m18-2v2M7 9V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/></svg>
                    </template>
                    <template x-if="c.tipo === 'extra'">
                        <svg class="size-6" viewBox="0 0 24 24" fill="currentColor"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>
                    </template>
                </span>

                {{-- Urgente --}}
                <template x-if="c.urgente">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-red-600 text-white text-sm font-bold px-3 py-0.5 uppercase tracking-wide" x-text="t.urgente"></span>
                </template>

                {{-- Nota della reception: area separata, non cambia lo stato --}}
                <template x-if="c.nota">
                    <span role="button" tabindex="0" @click.stop="notaAperta = c" class="absolute top-0 right-0 size-14 flex items-start justify-end p-2">
                        <span class="size-9 flex items-center justify-center rounded-full bg-white text-slate-900 shadow text-xl">📝</span>
                    </span>
                </template>

                {{-- Segnalazioni aperte --}}
                <template x-if="c.segnalazioni > 0">
                    <span class="absolute bottom-2 left-2 size-8 flex items-center justify-center rounded-full bg-red-600 text-white">
                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 1 21h22L12 2zm0 6 1 7h-2l1-7zm0 9.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4z"/></svg>
                    </span>
                </template>

                <span class="text-6xl font-black leading-none tabular-nums tracking-tight" x-text="c.numero"></span>

                {{-- Stato: icona + parola --}}
                <span class="mt-2 flex items-center gap-1 text-lg font-semibold">
                    <template x-if="c.stato === 'in_pulizia'">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 4l6 6M4 20l7-7m-1-3 4 4-5 5H5v-4l5-5z"/></svg>
                    </template>
                    <template x-if="c.stato === 'pronta'">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><path d="m4 12 5 5L20 6"/></svg>
                    </template>
                    <span x-text="t[c.stato]"></span>
                </span>
            </button>
        </template>
    </main>

    {{-- Barra "Annulla" (5 secondi) --}}
    <div x-show="annullabile" x-transition.opacity
         class="fixed inset-x-3 bottom-28 z-30 rounded-2xl bg-slate-900 text-white shadow-xl overflow-hidden">
        <div class="flex items-center gap-3 p-3">
            <span class="text-2xl font-black tabular-nums" x-text="annullabile?.numero"></span>
            <span class="text-lg flex-1 truncate" x-text="annullabile ? t[annullabile.stato] : ''"></span>
            <button type="button" @click="annulla()" class="min-h-14 rounded-xl bg-white text-slate-900 px-5 text-xl font-bold">
                ↩ <span x-text="t.annulla"></span>
            </button>
        </div>
        <div class="h-1.5 bg-white/30">
            <template x-if="annullabile"><div class="h-full bg-white origin-left" :style="`animation: hk-scorri ${ANNULLA_MS}ms linear forwards`"></div></template>
        </div>
    </div>

    {{-- Messaggio breve ("Inviata") --}}
    <div x-show="avviso" x-transition.opacity class="fixed inset-x-0 top-24 z-40 flex justify-center pointer-events-none">
        <span class="rounded-full bg-green-600 text-white text-xl font-bold px-6 py-3 shadow-lg" x-text="avviso"></span>
    </div>

    {{-- Pulsante rosso --}}
    <div x-show="camere.length > 0" class="fixed inset-x-0 bottom-0 z-20 p-3 pb-[max(env(safe-area-inset-bottom),0.75rem)] bg-gradient-to-t from-slate-50 via-slate-50/90 to-transparent">
        <button type="button" @click="apriProblema()"
                class="w-full min-h-22 rounded-2xl bg-red-600 active:bg-red-700 text-white text-2xl font-bold shadow-lg flex items-center justify-center gap-3">
            <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.5" r="3.5"/></svg>
            <span x-text="t.problema"></span>
        </button>
    </div>

    {{-- Conferma "Riaprire?" --}}
    <div x-show="daRiaprire" x-transition.opacity class="fixed inset-0 z-50 bg-slate-900/70 flex items-end sm:items-center justify-center p-3">
        <div class="w-full max-w-md rounded-3xl bg-white p-5 text-center">
            <p class="text-6xl font-black tabular-nums" x-text="daRiaprire?.numero"></p>
            <p class="mt-2 text-2xl font-semibold" x-text="daRiaprire ? t.riaprire.replace(':numero', daRiaprire.numero) : ''"></p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <button type="button" @click="daRiaprire = null" class="min-h-24 rounded-2xl bg-slate-200 text-5xl font-bold" :aria-label="t.no">✗</button>
                <button type="button" @click="confermaRiapri()" class="min-h-24 rounded-2xl bg-yellow-300 text-5xl font-bold" :aria-label="t.si">✓</button>
            </div>
        </div>
    </div>

    {{-- Nota della reception --}}
    <div x-show="notaAperta" x-transition.opacity @click="notaAperta = null" class="fixed inset-0 z-50 bg-slate-900/70 flex items-end sm:items-center justify-center p-3">
        <div class="w-full max-w-md rounded-3xl bg-white p-5" @click.stop>
            <p class="text-sm font-semibold uppercase text-slate-500" x-text="t.nota_reception"></p>
            <p class="text-4xl font-black tabular-nums mt-1" x-text="notaAperta?.numero"></p>
            <p class="mt-3 text-2xl whitespace-pre-line" x-text="notaAperta?.nota"></p>
            <button type="button" @click="notaAperta = null" class="mt-5 w-full min-h-16 rounded-2xl bg-slate-900 text-white text-xl font-bold" x-text="t.chiudi"></button>
        </div>
    </div>

    {{-- Problema: 1) quale camera  2) foto + nota + invia --}}
    <input type="file" accept="image/*" capture="environment" class="hidden" x-ref="foto" @change="fotoScelta($event)">

    <div x-show="problema" x-transition.opacity class="fixed inset-0 z-50 bg-white flex flex-col">
        <div class="flex items-center justify-between bg-red-600 text-white px-4 py-3 pt-[max(env(safe-area-inset-top),0.75rem)]">
            <span class="text-2xl font-bold" x-text="problema?.camera ? problema.camera.numero : t.quale_camera"></span>
            <button type="button" @click="chiudiProblema()" class="size-14 flex items-center justify-center rounded-full bg-white/20 text-3xl" :aria-label="t.chiudi">✕</button>
        </div>

        {{-- Passo 1: scelta della camera --}}
        <template x-if="problema && !problema.camera">
            <div class="flex-1 overflow-y-auto p-3 grid grid-cols-3 gap-3 content-start">
                <template x-for="c in camere" :key="'p' + c.id">
                    <button type="button" @click="scegliCamera(c)" class="min-h-24 rounded-2xl bg-slate-100 active:bg-red-100 border-2 border-slate-300 text-4xl font-black tabular-nums" x-text="c.numero"></button>
                </template>
            </div>
        </template>

        {{-- Passo 2: foto, nota, invia --}}
        <template x-if="problema && problema.camera">
            <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-3">
                <button type="button" @click="$refs.foto.click()" class="relative w-full rounded-2xl bg-slate-100 border-2 border-dashed border-slate-400 min-h-56 flex flex-col items-center justify-center overflow-hidden">
                    <template x-if="problema.anteprima">
                        <img :src="problema.anteprima" alt="" class="absolute inset-0 size-full object-cover">
                    </template>
                    <template x-if="!problema.anteprima">
                        <svg class="size-20 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.5" r="3.5"/></svg>
                    </template>
                    <span class="relative mt-2 rounded-full bg-white/90 px-4 py-2 text-lg font-semibold"
                          x-text="problema.anteprima ? t.rifai_foto : t.scatta_foto"></span>
                </button>
                <textarea x-model="problema.nota" rows="2" maxlength="1000" :placeholder="t.nota_facoltativa"
                          class="w-full rounded-2xl border-2 border-slate-300 p-3 text-xl select-text"></textarea>
                <button type="button" @click="inviaProblema()" :disabled="!problema.blob && !problema.nota?.trim()"
                        class="w-full min-h-20 rounded-2xl bg-red-600 disabled:bg-slate-300 text-white text-2xl font-bold flex items-center justify-center gap-2">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M4 12 20 4l-6 16-3-7-7-1z"/></svg>
                    <span x-text="t.invia"></span>
                </button>
            </div>
        </template>
    </div>
</div>
@endsection
