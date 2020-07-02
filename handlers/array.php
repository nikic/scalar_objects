<?php
declare(strict_types=1);

namespace {
    const SORT_NONE = 0;

    const COMBINE_AS_KEY = 1;
    const COMBINE_AS_VALUE = 2;

    const CASE_UPPER_FIRST = 1000;
    const CASE_UPPER_WORDS = 2000;
    const INT_INDEX = 3000;

    const IGNORE_VALUE_COMPARATOR = 1;
    const BUILDIN_VALUE_COMPARATOR = 2;
    const IGNORE_KEY_COMPARATOR = 3;
    const BUILDIN_KEY_COMPARATOR = 4;
}

namespace arr {

    use Exception;

    class Handler
    {
        /**
         * @see https://www.php.net/manual/en/function.uksort.php
         * @see https://www.php.net/manual/en/function.ksort.php
         * @see https://www.php.net/manual/en/function.krsort.php
         * @see https://www.php.net/manual/en/function.array-reverse.php
         * Sort and/or reverse keys.
         */
        public static function sortKey(array $self, callable $sorter = null, bool $reverse = false, bool $insensitive = false): array
        {
            $sorter ??= SORT_REGULAR;
            $type = (is_callable($sorter) ? 0 : $sorter) | ($insensitive ? SORT_FLAG_CASE : 0);

            if ($sorter === SORT_NONE) {
                $sort = "0";
            } elseif (is_int($sorter)) {
                $sort = "1";
            } elseif (is_callable($sorter)) {
                $sort = "2";
            } else {
                throw new Exception("sorter must be callable or int");
            }

            switch ($sort.intval($reverse)) {
                case "21": // continue
                case "20": uksort($self, $sorter); break;
                case "11": krsort($self, $type); break;
                case "10": ksort($self, $type); break;
                case "01": $self = array_reverse($self, true); break;
                default:   throw new Exception("Neither sort or reverse performed. Please check the arguments");
            }
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.array-diff.php
         * @see http://php.net/manual/en/function.array-diff-assoc.php
         * @see http://php.net/manual/en/function.array-diff-uassoc.php
         * @see http://php.net/manual/en/function.array-udiff.php
         * @see http://php.net/manual/en/function.array-udiff-assoc.php
         * @see http://php.net/manual/en/function.array-udiff-uassoc.php
         * Compute difference of values, considering keys or not.
         */
        public static function diffValue(array $self, callable|int $valComparator, callable $keyComparator, iterable ...$arrays): array
        {
            $msg1 = "Only BUILDIN_VALUE_COMPARATOR can be used";
            $msg2 = "Only IGNORE_KEY_COMPARATOR or BUILDIN_KEY_COMPARATOR can be used";

            if ($valComparator == IGNORE_VALUE_COMPARATOR) throw new Exception($msg1);
            elseif ($valComparator == BUILDIN_VALUE_COMPARATOR) $val = "1";
            elseif (is_int($valComparator)) throw new Exception($msg1);
            else $val = "2";

            if ($keyComparator == IGNORE_KEY_COMPARATOR) $key = "0";
            elseif ($keyComparator == BUILDIN_KEY_COMPARATOR) $key = "1";
            elseif (is_int($keyComparator)) throw new Exception($msg2);
            else $key = "2";

            if ($val == "2") $arrays[] = $valComparator;
            if ($key == "2") $arrays[] = $keyComparator;

            switch ($val.$key) {
                case "10": return array_diff($self, ...$arrays);
                case "11": return array_diff_assoc($self, ...$arrays);
                case "12": return array_diff_uassoc($self, ...$arrays);
                case "20": return array_udiff($self, ...$arrays);
                case "21": return array_udiff_assoc($self, ...$arrays);
                case "22": return array_udiff_uassoc($self, ...$arrays);
                default: throw new Exception("...");
            }
        }

        /**
         * @see http://php.net/manual/en/function.array-intersect.php
         * @see http://php.net/manual/en/function.array-intersect-assoc.php
         * @see http://php.net/manual/en/function.array-intersect-uassoc.php
         * @see http://php.net/manual/en/function.array-uintersect.php
         * @see http://php.net/manual/en/function.array-uintersect-assoc.php
         * @see http://php.net/manual/en/function.array-uintersect-uassoc.php
         * Compute intersection of values, considering keys or not.
         */
        public static function intersectValue(array $self, callable|int $valComparator, callable $keyComparator, iterable ...$arrays): array
        {
            $msg1 = "Only BUILDIN_VALUE_COMPARATOR can be used";
            $msg2 = "Only IGNORE_KEY_COMPARATOR or BUILDIN_KEY_COMPARATOR can be used";

            if ($valComparator == IGNORE_VALUE_COMPARATOR) throw new Exception($msg1);
            elseif ($valComparator == BUILDIN_VALUE_COMPARATOR) $val = "1";
            elseif (is_int($valComparator)) throw new Exception($msg1);
            else $val = "2";

            if ($keyComparator == IGNORE_KEY_COMPARATOR) $key = "0";
            elseif ($keyComparator == BUILDIN_KEY_COMPARATOR) $key = "1";
            elseif (is_int($keyComparator)) throw new Exception($msg2);
            else $key = "2";

            if ($val == "2") $arrays[] = $valComparator;
            if ($key == "2") $arrays[] = $keyComparator;

            switch ($val.$key) {
                case "10": return array_intersect($self, ...$arrays);
                case "11": return array_intersect_assoc($self, ...$arrays);
                case "12": return array_intersect_uassoc($self, ...$arrays);
                case "20": return array_uintersect($self, ...$arrays);
                case "21": return array_uintersect_assoc($self, ...$arrays);
                case "22": return array_uintersect_uassoc($self, ...$arrays);
                default: throw new Exception("...");
            }
        }

        /**
         * @see http://php.net/manual/en/function.array-diff-key.php
         * @see http://php.net/manual/en/function.array-diff-ukey.php
         * Computes the difference using a callback function on the keys for comparison.
         */
        public static function diffKey(array $self, callable|int $keyComparator, iterable ...$arrays): array
        {
            if ($keyComparator == BUILDIN_KEY_COMPARATOR) {
                return array_diff_key($self, ...$arrays);
            } else {
                $arrays[] = $keyComparator;
                return array_diff_ukey($self, ...$arrays);
            }
        }

        /**
         * @see http://php.net/manual/en/function.array-intersect-key.php
         * @see http://php.net/manual/en/function.array-intersect-ukey.php
         * Computes the intersection using a callback function on the keys for comparison.
         */
        public static function intersectKey(array $self, callable $keyComparator, iterable ...$arrays): array
        {
            if ($keyComparator == BUILDIN_KEY_COMPARATOR) {
                return array_intersect_key($self, ...$arrays);
            } else {
                $arrays[] = $keyComparator;
                return array_intersect_ukey($self, ...$arrays);
            }
        }

        /**
         * @see http://php.net/manual/en/function.array-change-key-case.php
         * Changes the case of all keys.
         */
        public static function changeKeys(array $self, int $case = CASE_LOWER): array
        {
            $temp2 = array_keys($self);
            switch ($case) {
                case CASE_LOWER: // continue
                case CASE_UPPER: return array_change_key_case($self, $case);
                case CASE_UPPER_FIRST:
                    array_walk($temp2, fn($val) => ucfirst($val));
                    return array_combine($temp2, $self);
                case CASE_UPPER_WORDS:
                    array_walk($temp2, fn($val) => ucwords($val));
                    return array_combine($temp2, $self);
                case INT_INDEX: return array_values($self);
                default: throw new Exception("wrong keyword");
            }
        }

        /**
         * @see http://php.net/manual/en/function.array-chunk.php
         * Splits into chunks.
         */
        public static function chunk(array $self, int $size, bool $preserveKeys = false): array
        {
            return array_chunk($self, $size, $preserveKeys);
        }

        /**
         * @see http://php.net/manual/en/function.count.php
         * Counts all elements.
         */
        public static function count(array $self, int $mode = COUNT_NORMAL): int
        {
            return count($self, $mode);
        }

        /**
         * @see http://php.net/manual/en/function.array-merge.php
         * @see http://php.net/manual/en/function.array-merge-recursive.php
         * Merge one or more arrays.
         */
        public static function merge(array $self, bool $recursive = false, iterable ...$arrays): array
        {
            return $recursive ? array_merge_recursive($self, ...$arrays) : array_merge($self, ...$arrays);
        }

        /**
         * @see http://php.net/manual/en/function.array-replace-recursive.php
         * Replaces elements from passed arrays into the first array recursively.
         */
        public static function replace(array $self, bool $recursive = false, iterable ...$replacements): array
        {
            return $recursive ? array_replace_recursive($self, ...$replacements) : array_replace($self, ...$replacements);
        }

        /**
         * @see http://php.net/manual/en/function.array-reduce.php
         * Iteratively reduce to a single value using a callback function.
         */
        public static function reduce(array $self, callable $callback, mixed $initial = null): mixed
        {
            return array_reduce($self, $callback, $initial);
        }

        /**
         * @see http://php.net/manual/en/function.array-pop.php
         * Pops the element off the end.
         */
        public static function pop(array $self): mixed
        {
            return array_pop($self);
        }

        /**
         * @see http://php.net/manual/en/function.array-push.php
         * Pushes one or more elements onto the end.
         */
        public static function push(array $self, mixed ...$values): array
        {
            array_push($self, ...$values);
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.array-shift.php
         * Shifts an element off the beginning.
         */
        public static function shift(array $self): mixed
        {
            return array_shift($self);
        }

        /**
         * @see http://php.net/manual/en/function.array-unshift.php
         * Prepend one or more elements to the beginning.
         */
        public static function unshift(array $self, mixed ...$value): array
        {
            array_unshift($self, ...$value);
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.array-splice.php
         * Removes a portion and replace it with something else.
         */
        public static function splice(array $self, int $offset, int $length = null, mixed $replacement = null): array
        {
            array_splice($self, $offset, $length ?? count($self), $replacement ?? []);
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.array-slice.php
         * Extracts a slice.
         */
        public static function slice(array $self, int $offset, int $length = null, bool $preserveKeys = false): array
        {
            return array_slice($self, $offset, $length, $preserveKeys);
        }

        /**
         * @see http://php.net/manual/en/function.shuffle.php
         * randomizes the order of the elements in an array.
         */
        public static function shuffle(array $self): array
        {
            shuffle($self);
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.array-rand.php
         * Picks one or more random entries.
         */
        public static function random(array $self, int $num = 1): array
        {
            $temp = array_rand($self, $num);
            return $num > 1 ? $temp : [$temp];
        }

        /**
         * @see http://php.net/manual/en/function.array-pad.php
         * Pads to the specified length with a value.
         */
        public static function pad(array $self, int $size, mixed $value): array
        {
            return array_pad($self, $size, $value);
        }

        /**
         * @see http://php.net/manual/en/function.array-key-exists.php
         * Checks if the given key or index exists in the array.
         */
        public static function keyExists(array $self, string|int $key): bool
        {
            return key_exists($key, $self);
        }

        /**
         * @see http://php.net/manual/en/function.array-filter.php
         * Filters elements using a callback function.
         */
        public static function filter(array $self, callable $filter): array
        {
            return array_filter($self, $filter, ARRAY_FILTER_USE_BOTH);
        }

        /**
         * @see http://php.net/manual/en/function.array-walk.php
         * @see http://php.net/manual/en/function.array-walk-recursive.php
         * Apply a callback function recursively to every elements.
         */
        public static function walk(array $self, callable $callback, bool $recursive = false, mixed $userData = null): array
        {
            $recursive ? array_walk_recursive($self, $callback, $userData) : array_walk($self, $callback, $userData);
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.array-map.php
         * Applies the callback to the elements.
         */
        public static function map(array $self, callable $mapper, iterable ...$arrays): array
        {
            return array_map($mapper, $self, ...$arrays);
        }

        /**
         * @see http://php.net/manual/en/function.array-multisort.php
         * Sort multiple or multi-dimensional arrays.
         */
        public static function multisort(array $self, mixed $order = SORT_ASC, mixed $flags = SORT_REGULAR): array
        {
            array_multisort($self, $order, $flags);
            return $self;
        }

        /**
         * @see https://www.php.net/manual/en/function.array-key-first.php
         * Gets the first key of an array.
         */
        public static function firstKey(array $self): int|string
        {
            return array_key_first($self);
        }

        /**
         * @see https://www.php.net/manual/en/function.array-key-last.php
         * Gets the last key of an array.
         */
        public static function lastKey(array $self): int|string
        {
            return array_key_last($self);
        }

        /**
         * inspired from Lodash
         * @see http://php.net/manual/en/function.array-shift.php
         * Shifts an element off the beginning.
         */
        public static function shifted(array $self): array
        {
            array_shift($self);
            return $self;
        }

        /**
         * inspired from Lodash
         * @see http://php.net/manual/en/function.array-pop.php
         * Pops the element off the end.
         */
        public static function popped(array $self): array
        {
            array_pop($self);
            return $self;
        }

        /**
         * from JS
         */
        public static function every(array $self, callable $callback): bool
        {
            $retVal = false;
            foreach ($self as $key => $val) {
                $retVal = $retVal && $callback($val, $key);
            }
            return $retVal;
        }

        /**
         * from JS
         */
        public static function some(array $self, callable $callback): bool
        {
            $retVal = false;
            foreach ($self as $key => $val) {
                $retVal = $retVal || $callback($val, $key);
            }
            return $retVal;
        }

        /**
         * @see https://www.php.net/manual/en/function.usort.php
         * @see https://www.php.net/manual/en/function.uasort.php
         * @see https://www.php.net/manual/en/function.sort.php
         * @see https://www.php.net/manual/en/function.arsort.php
         * @see https://www.php.net/manual/en/function.rsort.php
         * @see https://www.php.net/manual/en/function.asort.php
         * @see https://www.php.net/manual/en/function.array-reverse.php
         * @see https://www.php.net/manual/en/function.natcasesort.php
         * @see https://www.php.net/manual/en/function.natsort.php
         * Sort and/or reverse values.
         */
        public static function sortValue(array $self, callable|int $sorter = SORT_REGULAR, bool $reverse = false, bool $insensitive = false, bool $assoc = false): array
        {
            $type = (is_callable($sorter) ? 0 : $sorter) | ($insensitive ? SORT_FLAG_CASE : 0);

            if ($sorter === SORT_NONE) {
                $sort = "0";
            } elseif (is_int($sorter)) {
                $sort = "1";
            } elseif (is_callable($sorter)) {
                $sort = "2";
            } else {
                throw new Exception("sorter must be callable or int");
            }

            switch ($sort.intval($reverse).intval($assoc)) {
                case "211": // continue
                case "201": uasort($self, $sorter); break;
                case "210": // continue
                case "200": usort($self, $sorter); break;
                case "111": arsort($self, $type); break;
                case "110": rsort($self, $type); break;
                case "101": asort($self, $type); break;
                case "100": sort($self, $type); break;
                case "011": // continue
                case "010": array_reverse($self, $assoc); break;
                default: throw new Exception("Neither sort or reverse performed. Please check the arguments");
            }
            return $self;
        }

        /**
         * @see http://php.net/manual/en/function.implode.php
         * Join elements with a string.
         */
        public static function implode(array $self, string $glue = ""): string
        {
            return implode($glue, $self);
        }

        /**
         * @see http://php.net/manual/en/function.array-sum.php
         * @see http://php.net/manual/en/function.max.php
         * @see http://php.net/manual/en/function.min.php
         * @see http://php.net/manual/en/function.array-product.php
         * Calculates the sum/avg/max/min/product of values.
         */
        public static function calc(array $self, string $operation, bool $convertNull = false): float|int|string
        {
            if ($convertNull) {
                $self = array_map(fn($item) => is_null($item) || is_nan($item) || is_infinite($item) ? 0 : $item, $self);
            }

            switch ($operation) {
                case "sum": return array_sum($self);
                case "avg": return array_sum($self) / count($self);
                case "max": return max($self);
                case "min": return min($self);
                case "product": return array_product($self);
                default: throw new Exception("...");
            }
        }

        /**
         * @see http://php.net/manual/en/function.array-count-values.php
         * Counts all the values.
         */
        public static function countValues(array $self): array
        {
            return array_count_values($self);
        }

        /**
         * @see http://php.net/manual/en/function.in-array.php
         * Checks if a value exists.
         */
        public static function valueExists(array $self, mixed $needle, bool $strict = false): bool
        {
            return in_array($needle, $self, $strict);
        }

        /**
         * @see http://php.net/manual/en/function.array-keys.php
         * @see http://php.net/manual/en/function.array-search.php
         * Searches a given value and returns the first corresponding key if successful.
         */
        public static function searchKeys(array $self, mixed $value, bool $strict = false): array
        {
            return array_keys($self, $value, $strict);
        }

        /**
         * @see http://php.net/manual/en/function.array-unique.php
         * Removes duplicate values.
         */
        public static function unique(array $self, int $flags = SORT_STRING): array
        {
            return array_unique($self, $flags);
        }

        /**
         * @see http://php.net/manual/en/function.array-flip.php
         * Exchanges all keys with their associated values.
         */
        public static function flip(array $self): array
        {
            return array_flip($self);
        }

        /**
         * @see http://php.net/manual/en/function.array-column.php
         * Returns the values from a single column.
         */
        public static function column(array $self, int|string $columnKey, int|string $indexKey = null): array
        {
            return array_column($self, $columnKey, $indexKey);
        }

        /**
         * @see http://php.net/manual/en/function.array-combine.php
         * Creates array using one array for keys and another for its values.
         * @param int $role possible value: COMBINE_AS_KEY or COMBINE_AS_VALUE
         */
        public static function combine(array $self, int $role, iterable $values): array
        {
            return $role == COMBINE_AS_KEY ? array_combine($self, $values) : array_combine($values, $self);
        }

        /**
         * @see http://php.net/manual/en/function.array-fill-keys.php
         * Fills with values, specifying keys.
         */
        public static function fillKeys(array $self, mixed $value): array
        {
            return array_fill_keys($self, $value);
        }
    }
}
