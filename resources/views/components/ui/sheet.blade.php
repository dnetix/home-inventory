@props(['close' => null, 'title' => null])

<div class="fixed inset-0 z-50" @if ($close) x-data x-on:keydown.escape.window="$wire.{{ $close }}()" @endif>
    <div class="absolute inset-0 bg-[rgba(8,10,15,0.42)] dark:bg-[rgba(0,0,0,0.6)]"
        @if ($close) wire:click="{{ $close }}" @endif></div>

    <div
        class="absolute inset-x-0 bottom-0 flex max-h-[88%] flex-col overflow-y-auto rounded-t-[26px] bg-surface px-5 pt-2.5 pb-8 shadow-up lg:inset-x-auto lg:top-1/2 lg:bottom-auto lg:left-1/2 lg:max-h-[80vh] lg:w-[460px] lg:-translate-x-1/2 lg:-translate-y-1/2 lg:rounded-[20px] lg:p-6 lg:shadow-lg">
        <div class="mx-auto mb-3.5 h-[5px] w-[38px] shrink-0 rounded-full bg-line-2 lg:hidden"></div>

        @if ($title)
            <h2 class="mb-4 text-[19px] font-bold tracking-[-0.2px]">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </div>
</div>
