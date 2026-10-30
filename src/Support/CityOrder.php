<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Support;

use Illuminate\Support\Facades\DB;

/**
 * Fills cities.sort_order: the configured featured cities first (in the configured order),
 * then every other city in Turkish alphabetical order (Ç after C, İ after I, Ş after S, ...),
 * so ordering by sort_order does not depend on the database collation.
 */
class CityOrder
{
    private const string ALPHABET = 'ABCÇDEFGĞHIİJKLMNOÖPRSŞTUÜVYZ';

    /**
     * @param  list<int>|null  $featured  plate codes; defaults to config('turkiye-package.featured_cities')
     * @return int number of cities updated
     */
    public static function apply(?array $featured = null): int
    {
        $table = config('turkiye-package.cities_table', 'cities');
        $featured = array_values(array_unique(array_map(intval(...), $featured ?? config('turkiye-package.featured_cities', []))));

        $cities = DB::table($table)->pluck('name', 'id')->all();
        $order = self::order($cities, $featured);

        DB::transaction(function () use ($table, $order): void {
            foreach ($order as $position => $id) {
                DB::table($table)->where('id', $id)->update(['sort_order' => $position + 1]);
            }
        });

        return count($order);
    }

    /**
     * @param  array<int, string>  $cities  id => name
     * @param  list<int>  $featured
     * @return list<int> city ids in display order
     */
    public static function order(array $cities, array $featured): array
    {
        $head = array_values(array_filter($featured, fn (int $id): bool => isset($cities[$id])));

        $rest = array_diff_key($cities, array_flip($head));
        uasort($rest, self::compare(...));

        return [...$head, ...array_keys($rest)];
    }

    /**
     * Compare two upper-case Turkish names letter by letter in Turkish alphabetical order.
     */
    public static function compare(string $a, string $b): int
    {
        $rank = static fn (string $char): int => ($pos = mb_strpos(self::ALPHABET, $char)) === false ? 100 + mb_ord($char) : $pos;

        $a = mb_str_split($a);
        $b = mb_str_split($b);

        // Letter by letter; a name that is a prefix of the other comes first.
        foreach ($a as $i => $char) {
            if (! isset($b[$i])) {
                return 1;
            }

            if (($cmp = $rank($char) <=> $rank($b[$i])) !== 0) {
                return $cmp;
            }
        }

        return count($a) <=> count($b);
    }
}
