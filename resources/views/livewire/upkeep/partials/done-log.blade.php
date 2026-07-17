<x-ui.section-label class="mb-3">Recently done</x-ui.section-label>
@if ($this->doneLog->isEmpty())
    <p class="text-[13.5px] font-medium text-ink-3">Nothing logged yet.</p>
@else
    <x-ui.card class="divide-y divide-line px-3.5">
        @foreach ($this->doneLog as $log)
            <div wire:key="log-{{ $log->id }}" class="flex items-center gap-3 py-3">
                <span class="w-11 shrink-0 text-center">
                    <span class="block text-xs font-semibold text-ink-3">{{ $log->completed_on->format('M') }}</span>
                    <span class="block text-base leading-none font-semibold text-ink-2 tabular-nums">{{ $log->completed_on->day }}</span>
                </span>
                <span class="flex size-7 shrink-0 items-center justify-center rounded-[9px] bg-good text-white">
                    <x-icon name="check" :size="15" :stroke="2.6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[14.5px] font-semibold">{{ $log->task }}</span>
                    @if ($log->upkeeper)
                        <span class="block text-xs font-medium text-ink-3">by {{ $log->upkeeper->name }}</span>
                    @endif
                </span>
            </div>
        @endforeach
    </x-ui.card>
@endif
