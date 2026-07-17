<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Place;
use App\Support\PlaceTree;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PlaceTreeTest extends TestCase
{
    /**
     * garage(1, 6000×3000×7000) → shelf(2, 900×400×1800) ; office(3, no size) → drawer(4, no size)
     * Items: drill(shelf, 250×220×90), bits(shelf, 220×50×120), tent(garage, 600×220×220), passport(drawer, no dim)
     */
    private function tree(): PlaceTree
    {
        return new PlaceTree(
            new Collection([
                $this->place(1, 'Garage', null, [6000, 3000, 7000]),
                $this->place(2, 'Shelf B', 1, [900, 400, 1800]),
                $this->place(3, 'Office', null, null),
                $this->place(4, 'Drawer 1', 3, null),
            ]),
            new Collection([
                $this->item('Cordless drill', 2, [250, 220, 90]),
                $this->item('Drill bit set', 2, [220, 50, 120]),
                $this->item('Camping tent', 1, [600, 220, 220]),
                $this->item('Passport', 4, null),
            ]),
        );
    }

    public function test_roots_and_children_are_sorted_by_label(): void
    {
        $tree = $this->tree();

        $this->assertSame(['Garage', 'Office'], $tree->roots()->pluck('label')->all());
        $this->assertSame(['Shelf B'], $tree->childrenOf(1)->pluck('label')->all());
    }

    public function test_descendant_ids_include_self_and_nested_children(): void
    {
        $this->assertSame([1, 2], $this->tree()->descendantIds(1));
        $this->assertSame([3, 4], $this->tree()->descendantIds(3));
    }

    public function test_items_under_a_place_include_descendant_items(): void
    {
        $tree = $this->tree();

        $this->assertCount(3, $tree->itemsUnder(1));
        $this->assertCount(1, $tree->itemsIn(1));
    }

    public function test_fill_sums_item_volumes_against_capacity(): void
    {
        $fill = $this->tree()->fill(2);

        $this->assertSame(648.0, $fill->capacityLitres);
        $this->assertEqualsWithDelta(6.27, $fill->usedLitres, 0.001);
        $this->assertSame(2, $fill->measuredCount);
        $this->assertSame(2, $fill->totalCount);
        $this->assertEqualsWithDelta(0.9676, $fill->percent(), 0.001);
        $this->assertFalse($fill->isOverCapacity());
    }

    public function test_fill_counts_unmeasured_items_without_volume(): void
    {
        $fill = $this->tree()->fill(3);

        $this->assertNull($fill->capacityLitres);
        $this->assertNull($fill->percent());
        $this->assertSame(0, $fill->measuredCount);
        $this->assertSame(1, $fill->totalCount);
    }

    public function test_fill_flags_over_capacity(): void
    {
        $tree = new PlaceTree(
            new Collection([$this->place(1, 'Tiny bin', null, [100, 100, 100])]),
            new Collection([$this->item('Brick', 1, [90, 90, 90], qty: 3)]),
        );

        $fill = $tree->fill(1);

        $this->assertTrue($fill->isOverCapacity());
        $this->assertSame(100.0, $fill->percent());
    }

    public function test_quantity_multiplies_used_volume(): void
    {
        $tree = new PlaceTree(
            new Collection([$this->place(1, 'Floor', null, [4000, 2000, 2500])]),
            new Collection([$this->item('Winter tires ×4', 1, [630, 630, 210], qty: 4)]),
        );

        $this->assertEqualsWithDelta(333.396, $tree->fill(1)->usedLitres, 0.001);
    }

    public function test_breadcrumb_walks_from_root_to_leaf(): void
    {
        $this->assertSame(['Garage', 'Shelf B'], $this->tree()->breadcrumb(2));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}|null  $dim
     */
    private function place(int $id, string $label, ?int $parentId, ?array $dim): Place
    {
        $place = new Place(['label' => $label, 'glyph' => 'box', 'parent_id' => $parentId, 'dim' => $dim]);
        $place->id = $id;

        return $place;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}|null  $dim
     */
    private function item(string $name, int $placeId, ?array $dim, int $qty = 1): Item
    {
        $item = new Item(['name' => $name, 'place_id' => $placeId, 'dim' => $dim, 'qty' => $qty]);

        return $item;
    }
}
