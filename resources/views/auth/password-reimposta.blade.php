@extends('layouts.app')
@section('titolo', $invito ? 'Benvenuto' : 'Nuova password')

@section('content')
<div class="max-w-sm mx-auto mt-16 bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-1">{{ $invito ? 'Benvenuto in Housekeep' : 'Nuova password' }}</h1>
    <p class="text-slate-500 mb-6">{{ $invito ? 'Scegli la password per il tuo accesso.' : 'Scegli una nuova password.' }} Almeno 10 caratteri.</p>
    <form method="POST" action="{{ route('password.salva') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="invito" value="{{ $invito ? 1 : 0 }}">
        <label class="block"><span class="text-sm font-medium">Email</span>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="block"><span class="text-sm font-medium">Password</span>
            <input type="password" name="password" required minlength="10" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="block"><span class="text-sm font-medium">Ripeti la password</span>
            <input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <button class="w-full rounded-lg bg-slate-900 text-white py-2.5 font-semibold">Salva ed entra</button>
    </form>
</div>
@endsection
