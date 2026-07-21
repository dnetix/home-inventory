@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

{{-- A real favicon: without one, Chrome re-requests /favicon.ico on every
     URL change (e.g. the search box's ?q= sync) — one wasted hit per update --}}
<link rel="icon" href="/favicon.svg" type="image/svg+xml">

<script>
    window.applyTheme = () => {
        const theme = localStorage.getItem('hi-theme') ?? 'system';
        const dark = theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    };
    window.applyTheme();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
