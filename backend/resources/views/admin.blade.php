<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'ForTechLead') }} Admin</title>

        @fonts
        @vite(['resources/css/admin.css', 'resources/js/admin.ts'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
