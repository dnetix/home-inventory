<?php

namespace App\Casts;

use App\Support\Dimensions;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the width/height/depth millimeter columns to a single Dimensions value object.
 */
class DimensionsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Dimensions
    {
        if (! isset($attributes['width'], $attributes['height'], $attributes['depth'])) {
            return null;
        }

        return new Dimensions((int) $attributes['width'], (int) $attributes['height'], (int) $attributes['depth']);
    }

    /**
     * @return array{width: ?int, height: ?int, depth: ?int}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return ['width' => null, 'height' => null, 'depth' => null];
        }

        if (is_array($value)) {
            $value = Dimensions::fromArray($value);
        }

        return [
            'width' => $value->width,
            'height' => $value->height,
            'depth' => $value->depth,
        ];
    }
}
