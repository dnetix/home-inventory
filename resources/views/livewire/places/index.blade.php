<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col lg:mx-0 lg:max-w-4xl">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Places</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">
                {{ $this->stats['places'] }} locations · {{ $this->stats['items'] }} items
            </p>
        </div>
        <x-ui.icon-btn icon="plus" accent wire:click="openEditor" />
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Places"
            :subtitle="$this->stats['places'] . ' locations · ' . $this->stats['items'] . ' items'" />
    @endteleport

    @teleport('#topbar-actions')
        <x-ui.btn variant="tonal" size="sm" wire:click="openEditor">
            <x-icon name="plus" :size="16" /> Add location
        </x-ui.btn>
    @endteleport

    <div class="flex-1 px-5 pt-1 pb-6 lg:px-[30px] lg:pt-4">
        <div class="mb-3 flex items-center justify-between">
            <x-ui.section-label>Rooms</x-ui.section-label>
            <button type="button" wire:click="toggleAll" class="cursor-pointer text-[13px] font-bold text-accent">
                Expand / collapse all
            </button>
        </div>

        @if ($this->tree->roots()->isEmpty())
            <x-empty-state icon="map-pin" title="No places yet" sub="Add your first room with the + button." />
        @else
            <x-ui.card class="px-2.5 py-0.5">
                @foreach ($this->tree->roots() as $root)
                    @include('livewire.places.partials.tree-row', ['place' => $root, 'depth' => 0, 'first' => $loop->first])
                @endforeach
            </x-ui.card>
        @endif
    </div>

    {{-- Create sheet --}}
    @if ($editorOpen)
        @include('livewire.places.partials.editor-sheet', ['title' => 'New location', 'cta' => 'Add location', 'deletable' => false])
    @endif
</div>
