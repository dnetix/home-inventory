{{-- Compact tag chips for list rows and cards — rendered only while the
     "Show tags" display toggle is on. Expects $item with tags loaded. --}}
<span class="flex flex-wrap gap-1">
    @foreach ($item->tags as $rowTag)
        <span class="inline-flex items-center gap-1 rounded-full border border-line-2 px-[7px] py-px text-[10.5px] font-semibold whitespace-nowrap text-ink-2">
            <span class="size-1.5 shrink-0 rounded-[2px]" style="background: {{ $rowTag->color }}"></span>{{ $rowTag->label }}
        </span>
    @endforeach
</span>
