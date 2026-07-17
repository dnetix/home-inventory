@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <x-layouts.head :title="$title" />
</head>

<body class="min-h-dvh">
    {{ $slot }}
    @livewireScripts
</body>

</html>
