@extends('layouts.app')
@section('titolo', $hotel->nome)

@section('content')
<a href="{{ route('admin.index') }}" class="text-sm underline text-slate-600">← Hotel</a>
<h1 class="text-2xl font-bold mt-2 mb-4">{{ $hotel->nome }}</h1>

<div class="grid lg:grid-cols-2 gap-6 items-start">
    <form method="POST" action="{{ route('admin.hotel.update', $hotel) }}" class="bg-white rounded-xl shadow-sm p-5 space-y-4">
        @csrf @method('PUT')
        <h2 class="font-semibold">Dati dell'hotel</h2>
        @include('admin._hotel-campi')
        <button class="rounded-lg bg-slate-900 text-white px-5 py-2 font-semibold">Salva</button>
    </form>

    <div class="space-y-6">
        <section class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-semibold mb-3">Utenti della reception</h2>
            <div class="space-y-2 mb-4">
                @forelse ($utenti as $u)
                    <div @class(['flex flex-wrap items-center gap-2 border rounded-lg p-2', 'opacity-60' => ! $u->attivo])>
                        <form method="POST" action="{{ route('admin.utenti.update', $u) }}" class="flex flex-wrap items-center gap-2 flex-1" x-data="{ c: false }" @input="c = true">
                            @csrf @method('PUT')
                            <input name="nome" value="{{ $u->nome }}" required class="border rounded-lg px-2 py-1.5 w-32" aria-label="Nome">
                            <input type="email" name="email" value="{{ $u->email }}" required class="border rounded-lg px-2 py-1.5 flex-1 min-w-40" aria-label="Email">
                            <label class="text-sm flex items-center gap-1"><input type="checkbox" name="attivo" value="1" @checked($u->attivo)> attivo</label>
                            <button x-show="c" x-cloak class="rounded-lg bg-slate-900 text-white px-3 py-1.5 text-sm">Salva</button>
                        </form>
                        @if ($u->attivo)
                            <form method="POST" action="{{ route('admin.utenti.invito', $u) }}">
                                @csrf
                                <button class="rounded-lg border px-3 py-1.5 text-sm" title="Rimanda l'email per scegliere la password">✉ Reinvia invito</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">Nessun utente: aggiungine uno qui sotto.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('admin.utenti.store', $hotel) }}" class="flex flex-wrap items-end gap-2 border-t pt-4">
                @csrf
                <label class="text-sm">Nome<br><input name="nome" required maxlength="100" class="border rounded-lg px-2 py-2 w-36"></label>
                <label class="text-sm flex-1 min-w-48">Email<br><input type="email" name="email" required class="border rounded-lg px-2 py-2 w-full"></label>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">Aggiungi e invita</button>
            </form>
            <p class="text-xs text-slate-500 mt-2">L'utente riceve un'email con un link (valido 7 giorni) per scegliere la password. Le password non viaggiano mai per email.</p>
        </section>

        <section class="bg-white rounded-xl shadow-sm p-5 border border-red-200" x-data="{ aperto: false }">
            <h2 class="font-semibold text-red-700">Elimina hotel</h2>
            <p class="text-sm text-slate-600 mt-1">Cancella <b>tutto</b>: camere, addette, prenotazioni, storico, segnalazioni con foto e utenti. Non si può annullare. Per sospendere soltanto, togli la spunta "Hotel attivo".</p>
            <button type="button" @click="aperto = true" x-show="!aperto" class="mt-3 rounded-lg border border-red-300 text-red-700 px-4 py-2 font-semibold">Elimina…</button>
            <form x-show="aperto" x-cloak method="POST" action="{{ route('admin.hotel.destroy', $hotel) }}" class="mt-3 space-y-2">
                @csrf @method('DELETE')
                <label class="block text-sm">Scrivi <b>{{ $hotel->nome }}</b> per confermare
                    <input name="conferma" autocomplete="off" class="mt-1 w-full border rounded-lg px-3 py-2"></label>
                <button class="rounded-lg bg-red-600 text-white px-4 py-2 font-semibold">Elimina definitivamente</button>
            </form>
        </section>
    </div>
</div>
@endsection
