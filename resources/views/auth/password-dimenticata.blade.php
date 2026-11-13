@extends('layouts.app')
@section('titolo', 'Password dimenticata')

@section('content')
<div class="max-w-sm mx-auto mt-16 bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-1">Password dimenticata</h1>
    <p class="text-slate-500 mb-6">Scrivi la tua email: ti mandiamo un link per sceglierne una nuova.</p>
    <form method="POST" action="{{ route('password.invia') }}" class="space-y-4">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nome@hotel.it" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <button class="w-full rounded-lg bg-slate-900 text-white py-2.5 font-semibold">Mandami il link</button>
    </form>
    <a href="{{ route('login') }}" class="block mt-4 text-sm underline text-slate-600">← Torna all'accesso</a>
</div>
@endsection
