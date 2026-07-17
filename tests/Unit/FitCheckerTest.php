<?php

namespace Tests\Unit;

use App\Enums\FitStatus;
use App\Models\Item;
use App\Models\Place;
use App\Support\FitChecker;
use App\Support\PlaceTree;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class FitCheckerTest extends TestCase
{
    public function test_unknown_when_item_has_no_dimensions(): void
    {
        $place = $this->place(1, [900, 400, 1800]);

        $result = (new FitChecker)->check($this->item(null), $place, $this->tree([$place]));

        $this->assertSame(FitStatus::Unknown, $result->status);
    }

    public function test_unknown_when_place_has_no_dimensions(): void
    {
        $place = $this->place(1, null);

        $result = (new FitChecker)->check($this->item([100, 100, 100]), $place, $this->tree([$place]));

        $this->assertSame(FitStatus::Unknown, $result->status);
    }

    public function test_too_big_when_bounding_box_cannot_fit_even_rotated(): void
    {
        $bin = $this->place(1, [600, 420, 400]);

        $result = (new FitChecker)->check($this->item([1000, 100, 100]), $bin, $this->tree([$bin]));

        $this->assertSame(FitStatus::TooBig, $result->status);
    }

    public function test_fits_in_a_roomy_empty_place(): void
    {
        $shelf = $this->place(1, [900, 400, 1800]);

        $result = (new FitChecker)->check($this->item([250, 220, 90]), $shelf, $this->tree([$shelf]));

        $this->assertSame(FitStatus::Fit, $result->status);
        $this->assertSame(648.0, $result->capacityLitres);
        $this->assertSame(4.95, $result->neededLitres);
    }

    public function test_full_when_remaining_volume_is_not_enough(): void
    {
        // 8 L box holding ~6.86 L; a 3.4 L item cannot fit in the remaining ~1.14 L.
        $box = $this->place(1, [200, 200, 200]);
        $occupant = $this->item([190, 190, 190], placeId: 1);

        $result = (new FitChecker)->check($this->item([150, 150, 150]), $box, $this->tree([$box], [$occupant]));

        $this->assertSame(FitStatus::Full, $result->status);
    }

    public function test_tight_when_remaining_space_after_fit_is_under_ten_percent_of_capacity(): void
    {
        // Capacity 100 L, 85 L used; a 10 L item leaves 5 L < 10 % of capacity.
        $crate = $this->place(1, [1000, 500, 200]);
        $occupant = $this->item([850, 500, 200], placeId: 1);

        $result = (new FitChecker)->check($this->item([500, 200, 100]), $crate, $this->tree([$crate], [$occupant]));

        $this->assertSame(FitStatus::Tight, $result->status);
        $this->assertEqualsWithDelta(15.0, $result->remainingLitres(), 0.001);
    }

    public function test_volume_check_counts_items_in_descendant_places(): void
    {
        // The occupant sits in a child bin, but still consumes the parent's capacity.
        $crate = $this->place(1, [1000, 500, 200]);
        $bin = $this->place(2, [900, 450, 200], parentId: 1);
        $occupant = $this->item([850, 500, 200], placeId: 2);

        $result = (new FitChecker)->check($this->item([500, 200, 100]), $crate, $this->tree([$crate, $bin], [$occupant]));

        $this->assertSame(FitStatus::Tight, $result->status);
    }

    /**
     * @param  list<Place>  $places
     * @param  list<Item>  $items
     */
    private function tree(array $places, array $items = []): PlaceTree
    {
        return new PlaceTree(new Collection($places), new Collection($items));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}|null  $dim
     */
    private function place(int $id, ?array $dim, ?int $parentId = null): Place
    {
        $place = new Place(['label' => 'Place '.$id, 'glyph' => 'box', 'parent_id' => $parentId, 'dim' => $dim]);
        $place->id = $id;

        return $place;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}|null  $dim
     */
    private function item(?array $dim, ?int $placeId = null): Item
    {
        return new Item(['name' => 'Test item', 'place_id' => $placeId, 'dim' => $dim]);
    }
}
