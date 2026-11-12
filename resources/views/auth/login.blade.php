@extends('layouts.app')
@section('titolo', 'Accedi')

@section('content')
<div class="max-w-sm mx-auto mt-16 bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-1">Housekeep Hotel</h1>
    <p class="text-slate-500 mb-6">Accesso reception</p>

    <form method="POST" action="{{ url('/login') }}" class="space-y-4">
        @csrf
        <label class="block">
            <span class="text-sm font-medium">Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded-lg border-slate-300 border px-3 py-2">
        </label>
        <label class="block">
            <span class="text-sm font-medium">Password</span>
            <input type="password" name="password" required autocomplete="current-password"
                   class="mt-1 w-full rounded-lg border-slate-300 border px-3 py-2">
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="ricordami" value="1"> Ricordami su questo computer
        </label>
        @error('email')
            <p class="text-red-700 text-sm">{{ $message }}</p>
        @enderror
        <button class="w-full rounded-lg bg-slate-900 text-white py-2.5 font-semibold">Accedi</button>
    </form>
</div>
@endsection
