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

<fieldset class="mt-6 border-t pt-4 grid md:grid-cols-3 gap-4">
    <legend class="text-sm font-semibold text-slate-600 md:col-span-3">Abbonamento</legend>
    <label class="block"><span class="text-sm font-medium">Piano</span>
        <select name="piano" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">—</option>
            @foreach (\App\Models\Hotel::PIANI as $k => $n)<option value="{{ $k }}" @selected(old('piano', $hotel->piano) === $k)>{{ $n }}</option>@endforeach
        </select></label>
    <label class="block"><span class="text-sm font-medium">Fine prova gratuita</span>
        <input type="date" name="prova_fino_al" value="{{ old('prova_fino_al', $hotel->prova_fino_al?->toDateString()) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <label class="block"><span class="text-sm font-medium">Pagato fino al</span>
        <input type="date" name="pagato_fino_al" value="{{ old('pagato_fino_al', $hotel->pagato_fino_al?->toDateString()) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
    <p class="md:col-span-3 text-xs text-slate-500">Ricevi un'email 7 giorni prima della scadenza e una dopo, se non risulta pagato. L'hotel non viene bloccato in automatico.</p>
</fieldset>
