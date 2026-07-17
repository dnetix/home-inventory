<?php

namespace App\Support;

use App\Models\Home;
use App\Models\Item;
use App\Models\Place;
use Illuminate\Support\Collection;

/**
 * In-memory view of a home's whole location tree with its items, so tree
 * rendering and fill math never issue per-node queries: build it from two
 * queries (all places, all items) and walk it in PHP.
 */
final class PlaceTree
{
    /** @var Collection<int, Place> keyed by place id */
    private readonly Collection $places;

    /** @var Collection<int|string, Collection<int, Place>> children grouped by parent id ('' for roots) */
    private readonly Collection $childrenByParent;

    /** @var Collection<int|string, Collection<int, Item>> items grouped by place id */
    private readonly Collection $itemsByPlace;

    /**
     * @param  Collection<int, Place>  $places
     * @param  Collection<int, Item>  $items
     */
    public function __construct(Collection $places, Collection $items)
    {
        $this->places = $places->keyBy('id');
        $this->childrenByParent = $places
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->groupBy(fn (Place $place) => $place->parent_id ?? '');
        $this->itemsByPlace = $items->groupBy('place_id');
    }

    public static function forHome(Home $home): self
    {
        return new self(
            Place::forHome($home)->get(),
            Item::forHome($home)->get(),
        );
    }

    /**
     * @return Collection<int, Place>
     */
    public function roots(): Collection
    {
        return $this->childrenByParent->get('', new Collection)->values();
    }

    /**
     * @return Collection<int, Place>
     */
    public function childrenOf(int $placeId): Collection
    {
        return $this->childrenByParent->get($placeId, new Collection)->values();
    }

    public function find(int $placeId): ?Place
    {
        return $this->places->get($placeId);
    }

    /**
     * The place and every descendant, depth-first.
     *
     * @return list<int>
     */
    public function descendantIds(int $placeId): array
    {
        $ids = [$placeId];

        foreach ($this->childrenOf($placeId) as $child) {
            $ids = [...$ids, ...$this->descendantIds($child->id)];
        }

        return $ids;
    }

    /**
     * Items stored directly in the place or in any descendant.
     *
     * @return Collection<int, Item>
     */
    public function itemsUnder(int $placeId): Collection
    {
        return (new Collection($this->descendantIds($placeId)))
            ->flatMap(fn (int $id) => $this->itemsByPlace->get($id, new Collection))
            ->values();
    }

    /**
     * Items stored directly in the place (not descendants).
     *
     * @return Collection<int, Item>
     */
    public function itemsIn(int $placeId): Collection
    {
        return $this->itemsByPlace->get($placeId, new Collection)->values();
    }

    public function fill(int $placeId): PlaceFill
    {
        $usedLitres = 0.0;
        $measured = 0;
        $items = $this->itemsUnder($placeId);

        foreach ($items as $item) {
            $volume = $item->totalVolumeLitres();

            if ($volume !== null) {
                $usedLitres += $volume;
                $measured++;
            }
        }

        return new PlaceFill(
            capacityLitres: $this->find($placeId)?->capacityLitres(),
            usedLitres: $usedLitres,
            measuredCount: $measured,
            totalCount: $items->count(),
        );
    }

    /**
     * Whole-home storage as one aggregate fill: capacities are summed over
     * the topmost measured places only — a measured shelf inside a measured
     * garage is already part of the garage's interior, so counting both
     * would inflate the total.
     */
    public function totalStorage(): PlaceFill
    {
        $capacity = null;
        $used = 0.0;
        $measured = 0;
        $total = 0;

        foreach ($this->topmostMeasuredIds() as $id) {
            $fill = $this->fill($id);
            $capacity = ($capacity ?? 0.0) + $fill->capacityLitres;
            $used += $fill->usedLitres;
            $measured += $fill->measuredCount;
            $total += $fill->totalCount;
        }

        return new PlaceFill($capacity, $used, $measured, $total);
    }

    /**
     * Places that have a capacity set and no measured ancestor.
     *
     * @return list<int>
     */
    public function topmostMeasuredIds(): array
    {
        $out = [];

        $walk = function (Place $place) use (&$walk, &$out): void {
            if ($place->capacityLitres() !== null) {
                $out[] = $place->id;

                return;
            }

            foreach ($this->childrenOf($place->id) as $child) {
                $walk($child);
            }
        };

        foreach ($this->roots() as $root) {
            $walk($root);
        }

        return $out;
    }

    /**
     * Every place in depth-first display order with its nesting depth —
     * for pickers and flat tree renderings.
     *
     * @return Collection<int, array{place: Place, depth: int}>
     */
    public function flatten(): Collection
    {
        $out = new Collection;

        $walk = function (Place $place, int $depth) use (&$walk, $out): void {
            $out->push(['place' => $place, 'depth' => $depth]);

            foreach ($this->childrenOf($place->id) as $child) {
                $walk($child, $depth + 1);
            }
        };

        foreach ($this->roots() as $root) {
            $walk($root, 0);
        }

        return $out;
    }

    /**
     * Labels from the root down to the place itself.
     *
     * @return list<string>
     */
    public function breadcrumb(int $placeId): array
    {
        $labels = [];
        $current = $this->find($placeId);

        while ($current !== null) {
            array_unshift($labels, $current->label);
            $current = $current->parent_id === null ? null : $this->find($current->parent_id);
        }

        return $labels;
    }
}
