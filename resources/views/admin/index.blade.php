@extends('layouts.app')
@section('titolo', 'Hotel')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <h1 class="text-2xl font-bold">Hotel</h1>
    <a href="{{ route('admin.hotel.create') }}" class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">+ Nuovo hotel</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 border-b">
            <tr>
                <th class="p-3">Hotel</th><th class="p-3 text-right">Camere</th><th class="p-3 text-right">Addette</th><th class="p-3 text-right">Utenti</th>
                <th class="p-3 text-right">Pulite 7 gg</th><th class="p-3 text-right">Segnalazioni aperte</th><th class="p-3">Ultima attività</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($hotels as $h)
                @php $t = isset($ultima[$h->id]) ? \Carbon\Carbon::parse($ultima[$h->id]) : null; @endphp
                <tr @class(['opacity-50' => ! $h->attivo])>
                    <td class="p-3"><a href="{{ route('admin.hotel.show', $h) }}" class="font-semibold underline">{{ $h->nome }}</a>@unless ($h->attivo) <span class="text-xs">(disattivato)</span>@endunless</td>
                    <td class="p-3 text-right">{{ $camere[$h->id] ?? 0 }}</td>
                    <td class="p-3 text-right">{{ $addette[$h->id] ?? 0 }}</td>
                    <td class="p-3 text-right">{{ $utenti[$h->id] ?? 0 }}</td>
                    <td class="p-3 text-right">{{ $pulite7[$h->id] ?? 0 }}</td>
                    <td class="p-3 text-right">{{ $aperte[$h->id] ?? 0 }}</td>
                    <td @class(['p-3', 'text-red-700 font-semibold' => $h->attivo && (! $t || $t->lt(now()->subDays(7)))])>
                        {{ $t ? $t->timezone($h->fuso_orario)->format('d/m/Y H:i') : 'mai' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-slate-500">Nessun hotel. Creane uno con "Nuovo hotel".</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-2 text-xs text-slate-500">In rosso: hotel attivi senza attività da più di 7 giorni (ricevi anche un'email).</p>
@endsection
