<?php

namespace App\Livewire\Concerns;

use App\Support\IconLibrary;
use Livewire\Attributes\Computed;

/**
 * Quick search over the Lucide icon set for glyph pickers. The host
 * component must implement defaultGlyphs() (shown while the search is
 * blank) and currentGlyph() (kept in the options so the selection never
 * disappears from the grid).
 */
trait SearchesGlyphs
{
    public string $glyphSearch = '';

    /**
     * @return list<string>
     */
    #[Computed]
    public function glyphOptions(): array
    {
        $options = trim($this->glyphSearch) === ''
            ? $this->defaultGlyphs()
            : IconLibrary::search($this->glyphSearch);

        $current = $this->currentGlyph();

        if ($current !== '' && ! in_array($current, $options, true)) {
            array_unshift($options, $current);
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    abstract protected function defaultGlyphs(): array;

    abstract protected function currentGlyph(): string;
}
