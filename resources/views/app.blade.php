<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($panelVite = \Larasell\Admin\AdminPanel::resolve()->panelVite())
        {{ $panelVite->reactRefresh() }}
        {{ $panelVite('resources/js/app.tsx') }}

        @viteReactRefresh
        @if (\Larasell\Admin\AdminPanel::resolve()->scripts() !== [])
            @vite(\Larasell\Admin\AdminPanel::resolve()->scripts())
        @endif

        <x-inertia::head>
            <title>Admin</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />

        <script type="module">
            window.LarasellAdmin.boot();
        </script>
    </body>
</html>
