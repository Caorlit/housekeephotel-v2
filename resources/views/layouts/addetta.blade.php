<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Housekeep">
    <title>Housekeep</title>
    @isset($staff)
        <link rel="manifest" href="{{ route('addetta.manifest', $staff->token_accesso, false) }}">
    @endisset
    <link rel="icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/addetta.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased select-none">
    @yield('content')
</body>
</html>
