<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2a1109">
        <meta name="description" content="Twist &amp; Rolls: panadería artesanal por encargo en Xela. Rolls de canela, croissants, focaccias y trenzas. Arma tu caja, paga con tarjeta y recíbela en tu casa.">
        <link rel="icon" href="/images/favicon.png" type="image/png">
        @viteReactRefresh
        @vite('resources/js/app.tsx')
        <x-inertia::head>
            <title>{{ config('app.name', 'Twist &amp; Rolls') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
