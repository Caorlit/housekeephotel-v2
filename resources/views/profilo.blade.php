@extends('layouts.app')
@section('titolo', 'Il mio profilo')

@section('content')
<div class="max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Il mio profilo</h1>
    <form method="POST" action="{{ route('profilo.update') }}" class="bg-white rounded-xl shadow-sm p-5 space-y-4">
        @csrf @method('PUT')
        <label class="block"><span class="text-sm font-medium">Nome</span>
            <input name="nome" value="{{ old('nome', $user->nome) }}" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="block"><span class="text-sm font-medium">Email (per accedere e ricevere gli avvisi)</span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>

        <fieldset class="border-t pt-4 space-y-3">
            <legend class="text-sm font-semibold text-slate-600">Cambia password <span class="font-normal">(lascia vuoto per non cambiarla)</span></legend>
            <input type="password" name="password" minlength="10" autocomplete="new-password" placeholder="Nuova password (almeno 10 caratteri)" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" placeholder="Ripeti la nuova password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </fieldset>

        <label class="block border-t pt-4"><span class="text-sm font-medium">Password attuale</span>
            <span class="block text-xs text-slate-500">Serve solo se cambi email o password.</span>
            <input type="password" name="password_attuale" autocomplete="current-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>

        <button class="rounded-lg bg-slate-900 text-white px-5 py-2 font-semibold">Salva</button>
    </form>
</div>
@endsection
