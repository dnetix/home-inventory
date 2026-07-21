<?php

namespace App\Support;

/**
 * The Lucide icon set, generated into resources/icons/lucide.php by
 * scripts/build-icons.mjs (`npm run build:icons`). The map is immutable, so
 * it is loaded once per Octane worker — never per request or per icon.
 */
class IconLibrary
{
    /** @var array<string, array{s: string, t: string}>|null */
    private static ?array $icons = null;

    public static function has(string $name): bool
    {
        return isset(self::icons()[$name]);
    }

    /**
     * Inner SVG markup for an icon, ready to drop inside the x-icon wrapper.
     */
    public static function inner(string $name): ?string
    {
        return self::icons()[$name]['s'] ?? null;
    }

    /**
     * Icon names matching the term in their name or keywords, name-prefix
     * matches first.
     *
     * @return list<string>
     */
    public static function search(string $term, int $limit = 36): array
    {
        $term = mb_strtolower(trim($term));

        if ($term === '') {
            return [];
        }

        $prefix = [];
        $rest = [];

        foreach (self::icons() as $name => $icon) {
            if (str_starts_with($name, $term)) {
                $prefix[] = $name;
            } elseif (str_contains($name, $term) || str_contains($icon['t'], $term)) {
                $rest[] = $name;
            }

            if (count($prefix) >= $limit) {
                break;
            }
        }

        return array_slice([...$prefix, ...$rest], 0, $limit);
    }

    /**
     * @return array<string, array{s: string, t: string}>
     */
    private static function icons(): array
    {
        return self::$icons ??= require resource_path('icons/lucide.php');
    }
}
