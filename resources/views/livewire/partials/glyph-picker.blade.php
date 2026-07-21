{{-- Searchable icon grid for glyph fields. The host component uses the
     SearchesGlyphs trait (glyphSearch + glyphOptions) and has a form with
     a `glyph` property. --}}
<div class="mb-2.5 flex min-h-[42px] items-center gap-2 rounded-[11px] border border-line-2 bg-surface px-3 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
    <x-icon name="search" :size="15" :stroke="1.9" class="shrink-0 text-ink-3" />
    <input type="search" wire:model.live.debounce.300ms="glyphSearch" placeholder="Search icons — hammer, plant, guitar…"
        class="w-full bg-transparent py-2 text-[14px] font-medium text-ink outline-none placeholder:text-ink-3">
</div>
<div class="flex max-h-[176px] flex-wrap gap-2 overflow-y-auto">
    @forelse ($this->glyphOptions as $glyph)
        <button type="button" wire:key="glyph-{{ $glyph }}" wire:click="$set('form.glyph', '{{ $glyph }}')"
            title="{{ $glyph }}"
            class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border transition {{ $form->glyph === $glyph ? 'border-accent bg-accent-soft text-accent-ink' : 'border-line-2 bg-surface text-ink-2' }}">
            <x-icon :name="$glyph" :size="18" />
        </button>
    @empty
        <p class="py-1 text-[13px] font-medium text-ink-3">No icons match “{{ $glyphSearch }}”.</p>
    @endforelse
</div>
@error('form.glyph')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
