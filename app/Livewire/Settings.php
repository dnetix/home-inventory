<?php

namespace App\Livewire;

use App\Enums\Theme;
use App\Enums\Unit;
use App\Support\Dimensions;
use App\Support\UnitFormatter;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Settings')]
class Settings extends Component
{
    public string $unit;

    public string $theme;

    public bool $notifications;

    public function mount(): void
    {
        $user = auth()->user();

        $this->unit = $user->unit->value;
        $this->theme = $user->theme->value;
        $this->notifications = $user->notifications;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = Unit::from($unit)->value;

        auth()->user()->update(['unit' => $this->unit]);

        $this->dispatch('toast', message: 'Units updated');
    }

    public function setTheme(string $theme): void
    {
        $this->theme = Theme::from($theme)->value;

        auth()->user()->update(['theme' => $this->theme]);

        $this->dispatch('theme-changed', theme: $this->theme);
    }

    public function updatedNotifications(bool $value): void
    {
        auth()->user()->update(['notifications' => $value]);

        $this->dispatch('toast', message: $value ? 'Reminders on' : 'Reminders off');
    }

    /**
     * The drill's dimensions, formatted in the selected unit as a live example.
     */
    public function sample(): string
    {
        $formatter = UnitFormatter::for(Unit::from($this->unit));
        $dim = new Dimensions(250, 220, 90);

        return $formatter->dim($dim).' · '.$formatter->volume($dim->volumeLitres());
    }

    public function render(): View
    {
        return view('livewire.settings');
    }
}
