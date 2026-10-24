<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#fbf2e4">
        <meta name="description" content="RoloBox: roles de canela artesanales por encargo. Arma tu caja, paga con tarjeta y recíbela en tu casa.">
        <link rel="icon" href="/images/logo.svg" type="image/svg+xml">
        @viteReactRefresh
        @vite('resources/js/app.tsx')
        <x-inertia::head>
            <title>{{ config('app.name', 'RoloBox') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
