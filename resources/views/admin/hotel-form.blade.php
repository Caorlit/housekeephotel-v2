@extends('layouts.app')
@section('titolo', 'Nuovo hotel')

@section('content')
<a href="{{ route('admin.index') }}" class="text-sm underline text-slate-600">← Hotel</a>
<h1 class="text-2xl font-bold mt-2 mb-4">Nuovo hotel</h1>
<form method="POST" action="{{ route('admin.hotel.store') }}" class="bg-white rounded-xl shadow-sm p-5 space-y-4 max-w-3xl">
    @csrf
    @include('admin._hotel-campi')
    <button class="rounded-lg bg-slate-900 text-white px-5 py-2 font-semibold">Crea hotel</button>
</form>
@endsection
