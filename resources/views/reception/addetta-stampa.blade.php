<!DOCTYPE html>
<html lang="{{ $staff->lingua }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>QR {{ $staff->nome }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4; margin: 14mm; }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body class="bg-slate-200 text-slate-900">
    <div class="no-print max-w-[210mm] mx-auto flex justify-between items-center p-3">
        <span class="text-sm text-slate-600">Anteprima di stampa · lingua: {{ \App\Models\Staff::LINGUE[$staff->lingua] }}</span>
        <button onclick="window.print()" class="rounded-lg bg-slate-900 text-white px-4 py-2 font-semibold">🖨 Stampa</button>
    </div>

    <main class="bg-white max-w-[210mm] min-h-[270mm] mx-auto p-[12mm] flex flex-col items-center text-center shadow print:shadow-none" x-data>
        <p class="text-3xl font-bold">{{ str_replace(':nome', $staff->nome, $testi['ciao']) }} 👋</p>
        <p class="text-xl text-slate-600 mt-1">{{ $testi['stampa_titolo'] }}</p>

        <div class="my-8 size-[110mm]" x-init="disegnaQr($el, @js($link))"></div>

        <ol class="w-full max-w-[160mm] space-y-5 text-left text-2xl">
            <li class="flex items-center gap-5"><span class="shrink-0 size-16 rounded-2xl bg-slate-100 flex items-center justify-center text-4xl">📷</span><span>{{ $testi['stampa_1'] }}</span></li>
            <li class="flex items-center gap-5"><span class="shrink-0 size-16 rounded-2xl bg-slate-100 flex items-center justify-center text-4xl">📲</span><span>{{ $testi['stampa_2'] }}</span></li>
            <li class="flex items-center gap-5">
                <span class="shrink-0 flex gap-1"><span class="size-8 rounded-lg bg-yellow-300"></span><span class="size-8 rounded-lg bg-green-600"></span></span>
                <span>{{ $testi['stampa_3'] }}</span>
            </li>
            <li class="flex items-center gap-5"><span class="shrink-0 size-16 rounded-2xl bg-red-600 text-white flex items-center justify-center text-4xl">📷</span><span>{{ $testi['stampa_4'] }}</span></li>
        </ol>

        <p class="mt-auto pt-8 text-xs text-slate-400">Housekeep · {{ $staff->hotel->nome }} · {{ now()->format('d/m/Y') }}</p>
    </main>
</body>
</html>
