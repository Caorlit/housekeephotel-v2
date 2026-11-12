@extends('layouts.app')
@section('titolo', 'Addette')

@section('content')
<h1 class="text-2xl font-bold mb-4">Addette</h1>

<div class="grid md:grid-cols-[1fr_auto] gap-4 mb-6">
    <form method="POST" action="{{ route('reception.addette.store') }}" class="bg-white rounded-xl shadow-sm p-4">
        @csrf
        <h2 class="font-semibold mb-3">Nuova addetta</h2>
        <div class="flex flex-wrap items-end gap-2">
            <label class="text-sm flex-1 min-w-40">Nome<br><input name="nome" required maxlength="60" value="{{ old('nome') }}" placeholder="es. Maria" class="w-full border rounded-lg px-3 py-2"></label>
            <label class="text-sm">Lingua dell'app<br>
                <select name="lingua" class="border rounded-lg px-3 py-2">
                    @foreach ($lingue as $codice => $nome)<option value="{{ $codice }}">{{ $nome }}</option>@endforeach
                </select>
            </label>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">Aggiungi</button>
        </div>
    </form>

    <form method="POST" action="{{ route('reception.addette.pin-hotel') }}" class="bg-white rounded-xl shadow-sm p-4 md:w-80" x-data>
        @csrf
        <h2 class="font-semibold mb-2">PIN di sicurezza</h2>
        <label class="flex items-start gap-2 text-sm">
            <input type="hidden" name="pin_richiesto" value="0">
            <input type="checkbox" name="pin_richiesto" value="1" class="mt-1" @checked($hotel->pin_richiesto) @change="$el.form.submit()">
            <span>Chiedi il PIN alle addette che ne hanno uno. <span class="text-slate-500">Lo inseriscono una volta, poi il telefono è ricordato per 90 giorni.</span></span>
        </label>
    </form>
</div>

<div class="space-y-3">
    @forelse ($addette as $a)
        <div @class(['bg-white rounded-xl shadow-sm p-4', 'opacity-60' => ! $a->attivo]) x-data="{ qr: false }">
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('reception.addette.update', $a) }}" class="flex flex-wrap items-center gap-2 flex-1"
                      x-data="{ cambiata: false }" @input="cambiata = true">
                    @csrf @method('PUT')
                    <input name="nome" value="{{ $a->nome }}" required maxlength="60" class="border rounded-lg px-3 py-2 font-semibold text-lg w-44" aria-label="Nome">
                    <select name="lingua" class="border rounded-lg px-2 py-2" aria-label="Lingua">
                        @foreach ($lingue as $codice => $nome)<option value="{{ $codice }}" @selected($a->lingua === $codice)>{{ $nome }}</option>@endforeach
                    </select>
                    <label class="text-sm flex items-center gap-1"><input type="checkbox" name="attivo" value="1" @checked($a->attivo)> attiva</label>
                    @if ($a->pin)<span class="text-xs rounded-full bg-slate-200 px-2 py-0.5">🔒 PIN</span>@endif
                    <button x-show="cambiata" x-cloak class="rounded-lg bg-slate-900 text-white px-3 py-2 text-sm font-semibold">Salva</button>
                </form>

                @if ($a->attivo)
                    <button type="button" @click="qr = !qr" class="rounded-lg border px-3 py-2 text-sm font-semibold">▦ QR e link</button>
                    <a href="{{ route('reception.addette.stampa', $a) }}" target="_blank" class="rounded-lg border px-3 py-2 text-sm font-semibold">🖨 Stampa foglio</a>
                @endif
            </div>

            @if ($a->attivo)
                <div x-show="qr" x-cloak class="mt-4 grid md:grid-cols-[auto_1fr] gap-4 items-start border-t pt-4">
                    <div class="size-48 bg-white" x-effect="if (qr) disegnaQr($el, @js($a->linkPersonale()))"></div>
                    <div class="space-y-3 text-sm">
                        <p>Fai inquadrare questo codice a <b>{{ $a->nome }}</b> con la fotocamera del telefono, oppure mandale il link:</p>
                        <div class="flex gap-2">
                            <input readonly value="{{ $a->linkPersonale() }}" class="flex-1 border rounded-lg px-2 py-2 font-mono text-xs bg-slate-50" @focus="$el.select()">
                            <button type="button" class="rounded-lg border px-3 py-2 font-semibold" @click="copia(@js($a->linkPersonale()), $el)">Copia</button>
                        </div>
                        <p class="text-slate-500">Il link è personale: chi lo ha entra come {{ $a->nome }}. Se finisce in mani sbagliate, rigeneralo.</p>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('reception.addette.rigenera', $a) }}" onsubmit="return confirm('Il vecchio link e il vecchio QR di {{ $a->nome }} smetteranno di funzionare. Continuare?')">
                                @csrf
                                <button class="rounded-lg border border-red-300 text-red-700 px-3 py-2 font-semibold">↻ Rigenera link</button>
                            </form>
                            <form method="POST" action="{{ route('reception.addette.pin', $a) }}" class="flex gap-2">
                                @csrf
                                <input name="pin" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="PIN 4 cifre" class="w-28 border rounded-lg px-2 py-2" aria-label="Nuovo PIN">
                                <button class="rounded-lg border px-3 py-2 font-semibold">Imposta PIN</button>
                            </form>
                            @if ($a->pin)
                                <form method="POST" action="{{ route('reception.addette.pin', $a) }}">
                                    @csrf
                                    <button class="rounded-lg border px-3 py-2">Togli PIN</button>
                                </form>
                            @endif
                        </div>
                        @if ($a->pin && ! $hotel->pin_richiesto)
                            <p class="text-amber-700">Nota: il PIN è impostato ma non viene chiesto, perché "PIN di sicurezza" è spento.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @empty
        <p class="text-slate-500">Nessuna addetta. Aggiungine una qui sopra.</p>
    @endforelse
</div>
@endsection
