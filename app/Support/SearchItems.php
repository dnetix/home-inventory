<?php

namespace App\Support;

use App\Models\Item;
use App\Models\Place;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Answers "where is my X?" — matches items by name/note (full-text boolean
 * mode + substring fallback), and by tag, category, and place labels; a place
 * match includes items stored anywhere under that place. Every word of the
 * query must match something, so "kitchen drill" narrows instead of widening.
 */
final class SearchItems
{
    /**
     * InnoDB's default minimum full-text token size.
     */
    private const int FULLTEXT_MIN_LENGTH = 3;

    private const int MAX_WORDS = 5;

    /**
     * @return Builder<Item> filtered and relevance-ordered; paginate or limit at the call site
     */
    public function query(string $term, ?int $withinPlaceId = null): Builder
    {
        $words = Str::of($term)->squish()->lower()->explode(' ')->filter()->take(self::MAX_WORDS);

        $query = Item::query()->with(['category', 'place', 'tags']);

        if ($words->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $places = Place::query()->get(['id', 'parent_id', 'label']);

        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';
            $placeIds = $this->placeIdsMatching($places, $word);

            $query->where(function (Builder $q) use ($word, $like, $placeIds) {
                $q->where('name', 'like', $like)
                    ->orWhere('note', 'like', $like)
                    ->orWhereHas('category', fn (Builder $c) => $c->where('label', 'like', $like))
                    ->orWhereHas('tags', fn (Builder $t) => $t->where('label', 'like', $like));

                if (mb_strlen($word) >= self::FULLTEXT_MIN_LENGTH && ($boolean = $this->booleanTerm($word)) !== '') {
                    $q->orWhereFullText(['name', 'note'], $boolean, ['mode' => 'boolean']);
                }

                if ($placeIds !== []) {
                    $q->orWhereIn('place_id', $placeIds);
                }
            });
        }

        if ($withinPlaceId !== null) {
            $query->whereIn('place_id', $this->withDescendants($places, [$withinPlaceId]));
        }

        $first = $this->escapeLike($words->first());

        return $query->orderByRaw(
            'case when name like ? then 0 when name like ? then 1 else 2 end, name',
            [$first.'%', '%'.$first.'%'],
        );
    }

    /**
     * Ids of places whose label matches the word, plus all their descendants —
     * searching "garage" must find items on a shelf inside the garage.
     *
     * @param  Collection<int, Place>  $places
     * @return list<int>
     */
    private function placeIdsMatching(Collection $places, string $word): array
    {
        $matching = $places
            ->filter(fn (Place $place) => str_contains(mb_strtolower($place->label), $word))
            ->pluck('id')
            ->all();

        return $matching === [] ? [] : $this->withDescendants($places, $matching);
    }

    /**
     * @param  Collection<int, Place>  $places
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function withDescendants(Collection $places, array $ids): array
    {
        $childrenByParent = $places->groupBy('parent_id');
        $all = [];
        $queue = $ids;

        while ($queue !== []) {
            $id = array_shift($queue);

            if (isset($all[$id])) {
                continue;
            }

            $all[$id] = true;

            foreach ($childrenByParent->get($id, new Collection) as $child) {
                $queue[] = $child->id;
            }
        }

        return array_keys($all);
    }

    /**
     * A word as a prefix-matching boolean-mode term, stripped of operators.
     */
    private function booleanTerm(string $word): string
    {
        $clean = preg_replace('/[+\-<>()~*"@]/', '', $word) ?? '';

        return $clean === '' ? '' : '+'.$clean.'*';
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
