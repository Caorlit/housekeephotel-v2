<div class="grid md:grid-cols-2 gap-4">
    <label class="block"><span class="text-sm font-medium">Nome *</span>
        <input name="nome" value="{{ old('nome', $hotel->nome) }}" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <label class="block"><span class="text-sm font-medium">Fuso orario</span>
        <select name="fuso_orario" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            @foreach ($fusi as $f)<option value="{{ $f }}" @selected(old('fuso_orario', $hotel->fuso_orario) === $f)>{{ $f }}</option>@endforeach
        </select></label>
    <label class="block md:col-span-2"><span class="text-sm font-medium">Indirizzo</span>
        <input name="indirizzo" value="{{ old('indirizzo', $hotel->indirizzo) }}" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <label class="block"><span class="text-sm font-medium">Telefono</span>
        <input name="telefono" value="{{ old('telefono', $hotel->telefono) }}" maxlength="50" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <label class="block"><span class="text-sm font-medium">Email dell'hotel</span>
        <input type="email" name="email" value="{{ old('email', $hotel->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <label class="flex items-center gap-2"><input type="checkbox" name="pin_richiesto" value="1" @checked(old('pin_richiesto', $hotel->pin_richiesto))> Chiedi il PIN alle addette</label>
    <label class="flex items-center gap-2"><input type="checkbox" name="attivo" value="1" @checked(old('attivo', $hotel->attivo))> Hotel attivo</label>
</div>
