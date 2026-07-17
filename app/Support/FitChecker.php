<?php

namespace App\Support;

use App\Enums\FitStatus;
use App\Models\Item;
use App\Models\Place;

/**
 * Answers "will this item fit in that place?" with the prototype's heuristic:
 * a rotation-tolerant bounding-box check plus a remaining-volume check.
 * Not true 3-D packing — intentionally approximate.
 */
final class FitChecker
{
    /**
     * Remaining space under this share of capacity counts as a tight fit.
     */
    private const float TIGHT_THRESHOLD = 0.1;

    public function check(Item $item, Place $place, PlaceTree $tree): FitResult
    {
        if ($item->dim === null || $place->dim === null) {
            return new FitResult(FitStatus::Unknown);
        }

        $capacity = $place->dim->volumeLitres();
        $used = $tree->fill($place->id)->usedLitres;
        $needed = $item->totalVolumeLitres();
        $remaining = $capacity - $used;

        $status = match (true) {
            ! $item->dim->fitsWithin($place->dim) => FitStatus::TooBig,
            $needed > $remaining => FitStatus::Full,
            ($remaining - $needed) < $capacity * self::TIGHT_THRESHOLD => FitStatus::Tight,
            default => FitStatus::Fit,
        };

        return new FitResult($status, $capacity, $used, $needed);
    }
}
