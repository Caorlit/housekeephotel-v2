@extends('layouts.addetta')

@section('content')
<div class="min-h-dvh flex flex-col items-center justify-center p-4"
     x-data="{ pin: '', premi(n) { if (this.pin.length < 4) this.pin += n; if (this.pin.length === 4) this.$nextTick(() => this.$refs.form.submit()); } }">
    <p class="text-2xl font-bold">{{ __('addetta.ciao', ['nome' => $staff->nome]) }}</p>
    <p class="mt-1 text-xl text-slate-600">🔒 {{ __('addetta.pin_titolo') }}</p>

    @if ($errore)
        <p class="mt-3 rounded-xl bg-red-100 text-red-800 px-4 py-2 text-lg font-semibold">
            {{ isset($attendi) ? __('addetta.pin_attendi') : __('addetta.pin_errato') }}
        </p>
    @endif

    <form x-ref="form" method="POST" action="{{ route('addetta.pin', $staff->token_accesso, false) }}" class="mt-6">
        <input type="hidden" name="pin" :value="pin">
        <div class="flex gap-4 justify-center mb-6">
            <template x-for="i in 4"><span class="size-6 rounded-full border-4 border-slate-900" :class="pin.length >= i && 'bg-slate-900'"></span></template>
        </div>
        <div class="grid grid-cols-3 gap-3 w-72">
            @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                <button type="button" @click="premi('{{ $n }}')" class="h-22 rounded-2xl bg-white border-2 border-slate-300 text-4xl font-bold active:bg-slate-200">{{ $n }}</button>
            @endforeach
            <button type="button" @click="pin = pin.slice(0, -1)" class="h-22 rounded-2xl bg-slate-200 text-3xl" aria-label="{{ __('addetta.cancella') }}">⌫</button>
            <button type="button" @click="premi('0')" class="h-22 rounded-2xl bg-white border-2 border-slate-300 text-4xl font-bold active:bg-slate-200">0</button>
            <span></span>
        </div>
    </form>
</div>
@endsection
