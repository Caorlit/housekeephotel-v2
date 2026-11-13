@extends('layouts.app')
@section('titolo', 'Importa prenotazioni')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('reception.prenotazioni') }}" class="text-sm underline text-slate-600">← Prenotazioni</a>
    <h1 class="text-2xl font-bold mt-2 mb-4">Importa prenotazioni da file CSV</h1>

    <form method="POST" action="{{ route('reception.prenotazioni.anteprima') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-5 space-y-4">
        @csrf
        <label class="block">
            <span class="font-semibold">File CSV esportato dal gestionale</span>
            <input type="file" name="file" accept=".csv,text/csv,text/plain" required class="mt-2 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:text-white file:px-4 file:py-2">
        </label>
        <label class="block">
            <span class="font-semibold">Riassetto per le nuove prenotazioni</span>
            <select name="riassetto_ogni" class="mt-2 block border rounded-lg px-3 py-2">
                @foreach ($frequenze as $valore => $nome)
                    <option value="{{ $valore }}" @selected($valore === \App\Models\Stay::RIASSETTO_PREDEFINITO)>{{ $nome }}</option>
                @endforeach
            </select>
            <span class="text-xs text-slate-500">Si può cambiare poi per ogni prenotazione, toccandola nel planning.</span>
        </label>
        <button class="rounded-lg bg-slate-900 text-white px-5 py-2 font-semibold">Mostra anteprima</button>
        <p class="text-xs text-slate-500">Prima di salvare vedrai cosa verrà importato. Puoi ricaricare lo stesso file più volte: le prenotazioni già presenti vengono aggiornate, non duplicate.</p>
    </form>

    <div class="mt-6 text-sm text-slate-600 space-y-2">
        <p class="font-semibold text-slate-900">Che file serve</p>
        <p>Una riga per camera prenotata, con le colonne <b>Arrivo</b>, <b>Partenza</b> e la camera (<b>PMS</b> o <b>Camera</b>). Facoltative: <b>Codice</b> (numero di prenotazione) e <b>Pax</b>. Esempio:</p>
        <pre class="bg-white rounded-lg p-3 text-xs overflow-x-auto">Arrivo;Partenza;Cliente;Codice;Pax;Subtotale;PMS
10/10/2026;14/10/2026;ROSSI;GF7IB3;2;88;207-M+S-B</pre>
        <p>Dalla colonna PMS si prende il numero prima del trattino (207). <b>Nome del cliente e prezzi non vengono importati.</b></p>
    </div>
</div>
@endsection
