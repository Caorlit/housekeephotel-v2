@extends('layouts.app')
@section('titolo', 'Richieste di prova')

@section('content')
<h1 class="text-2xl font-bold mb-1">Richieste di prova</h1>
<p class="text-slate-600 mb-4">Arrivano dal modulo "Prova gratis" del sito. Per attivarne una: crea l'hotel (i dati sono già compilati) e aggiungi l'utente della reception con l'email della richiesta.</p>

<div class="space-y-3">
    @forelse ($richieste as $r)
        <div @class(['bg-white rounded-xl shadow-sm p-4', 'opacity-60' => in_array($r->stato, ['attivata', 'scartata'])])>
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-lg font-bold">{{ $r->hotel }}</span>
                @if ($r->citta)<span class="text-slate-500">{{ $r->citta }}</span>@endif
                <span class="text-xs rounded-full bg-slate-100 px-2 py-0.5">{{ strtoupper($r->lingua) }}</span>
                <span class="ml-auto text-sm text-slate-500">{{ $r->created_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</span>
            </div>
            <div class="mt-1 text-sm">
                <b>{{ $r->nome }}</b> · <a href="mailto:{{ $r->email }}" class="underline">{{ $r->email }}</a>
                @if ($r->telefono) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $r->telefono) }}" class="underline">{{ $r->telefono }}</a>@endif
                @if ($r->camere) · {{ $r->camere }} camere @endif
            </div>
            @if ($r->messaggio)<p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $r->messaggio }}</p>@endif
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.richieste.update', $r) }}" class="flex items-center gap-2">
                    @csrf @method('PUT')
                    <select name="stato" class="border rounded-lg px-2 py-1.5 text-sm" onchange="this.form.submit()">
                        @foreach ($stati as $k => $n)<option value="{{ $k }}" @selected($r->stato === $k)>{{ $n }}</option>@endforeach
                    </select>
                </form>
                @unless ($r->hotelCreato)
                    <a href="{{ route('admin.hotel.create', ['nome' => $r->hotel, 'email' => $r->email, 'telefono' => $r->telefono, 'indirizzo' => $r->citta, 'richiesta' => $r->id]) }}"
                       class="rounded-lg bg-slate-900 text-white px-3 py-1.5 text-sm font-semibold">+ Crea l'hotel</a>
                @else
                    <a href="{{ route('admin.hotel.show', $r->hotelCreato) }}" class="text-sm underline">Hotel creato: {{ $r->hotelCreato->nome }}</a>
                @endunless
            </div>
        </div>
    @empty
        <p class="text-slate-500">Nessuna richiesta per ora.</p>
    @endforelse
</div>
<div class="mt-4">{{ $richieste->links() }}</div>
@endsection
