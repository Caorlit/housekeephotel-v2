@extends('layouts.app')
@section('titolo', 'Tabellone')

@section('content')
<meta http-equiv="refresh" content="15">
<div class="flex items-baseline justify-between mb-4">
    <h1 class="text-2xl font-bold">Camere di oggi</h1>
    <span class="text-slate-500">{{ \Carbon\Carbon::parse($oggi)->locale('it')->translatedFormat('l j F Y') }} · si aggiorna ogni 15 secondi</span>
</div>

<div class="grid lg:grid-cols-[1fr_20rem] gap-6">
    <div class="grid grid-cols-[repeat(auto-fill,minmax(8rem,1fr))] gap-3 content-start">
        @forelse ($camere as $c)
            <div @class([
                'rounded-xl p-3 text-center',
                'bg-slate-200' => $c->stato === 'da_fare',
                'bg-yellow-300' => $c->stato === 'in_pulizia',
                'bg-green-600 text-white' => $c->stato === 'pronta',
                'ring-4 ring-red-600' => $c->urgente,
            ])>
                <div class="text-3xl font-black">{{ $c->room->numero }}</div>
                <div class="text-sm">{{ ['da_fare' => 'Da fare', 'in_pulizia' => 'In pulizia', 'pronta' => 'Pronta'][$c->stato] }}</div>
                <div class="text-xs opacity-75 truncate">{{ $c->staff?->nome ?? '—' }} · {{ $c->tipo }}</div>
            </div>
        @empty
            <p class="text-slate-500">Nessuna camera preparata per oggi.</p>
        @endforelse
    </div>

    <aside>
        <h2 class="font-bold mb-2">Segnalazioni aperte ({{ $segnalazioni->count() }})</h2>
        <div class="space-y-3">
            @foreach ($segnalazioni as $s)
                <div class="bg-white rounded-xl shadow p-3">
                    <div class="font-bold">Camera {{ $s->roomDay->room->numero }} <span class="font-normal text-slate-500">· {{ $s->staff?->nome }} · {{ $s->created_at->timezone(auth()->user()->hotel->fuso_orario)->format('H:i') }}</span></div>
                    @if ($s->foto_path)
                        <a href="{{ route('reception.foto', $s) }}" target="_blank"><img src="{{ route('reception.foto', $s) }}" alt="Foto" class="mt-2 rounded-lg max-h-40 object-cover w-full"></a>
                    @endif
                    @if ($s->nota)<p class="mt-2 text-sm">{{ $s->nota }}</p>@endif
                </div>
            @endforeach
        </div>
    </aside>
</div>
@endsection
