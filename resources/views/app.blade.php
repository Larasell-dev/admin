<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Runs before the first paint, so dark mode never flashes light. --}}
        <script>
            (() => {
                const query = window.matchMedia('(prefers-color-scheme: dark)');
                const apply = () => {
                    const theme = localStorage.getItem('larasell-admin-theme');
                    document.documentElement.classList.toggle(
                        'dark',
                        theme === 'dark' || (theme !== 'light' && query.matches),
                    );
                };

                apply();
                query.addEventListener('change', apply);
            })();
        </script>

        @php($panelVite = \Larasell\Admin\AdminPanel::resolve()->panelVite())
        {{ $panelVite->fonts() }}
        {{ $panelVite->reactRefresh() }}
        {{ $panelVite('resources/js/app.tsx') }}

        @viteReactRefresh
        @if (\Larasell\Admin\AdminPanel::resolve()->scripts() !== [])
            @vite(\Larasell\Admin\AdminPanel::resolve()->scripts())
        @endif

        @head
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />

        <script type="module">
            window.LarasellAdmin.boot();
        </script>
    </body>
</html>
