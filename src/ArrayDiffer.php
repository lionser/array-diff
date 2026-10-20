<?php

declare(strict_types=1);

namespace Lionser\ArrayDiff;

final class ArrayDiffer
{
    public function __construct(private readonly string $separator = '.')
    {
        if ($separator === '') {
            throw new \InvalidArgumentException('Separator must not be empty.');
        }
    }

    /**
     * @param array<array-key, mixed> $before
     * @param array<array-key, mixed> $after
     *
     * @return list<Change>
     */
    public function diff(array $before, array $after): array
    {
        return iterator_to_array($this->walk($before, $after, ''), false);
    }

    /**
     * @param array<array-key, mixed> $before
     * @param array<array-key, mixed> $after
     *
     * @return list<array{field: string, old: mixed, new: mixed}>
     */
    public function diffToArray(array $before, array $after): array
    {
        return array_map(
            static fn (Change $change): array => $change->toArray(),
            $this->diff($before, $after),
        );
    }

    /**
     * @param array<array-key, mixed> $before
     * @param array<array-key, mixed> $after
     *
     * @return \Generator<int, Change>
     */
    private function walk(array $before, array $after, string $path): \Generator
    {
        foreach ($before + $after as $key => $_) {
            $field = $path === '' ? (string) $key : $path . $this->separator . $key;

            $hasBefore = array_key_exists($key, $before);
            $hasAfter  = array_key_exists($key, $after);

            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($hasBefore && $hasAfter && is_array($old) && is_array($new) && $this->isMap($old) && $this->isMap($new)) {
                yield from $this->walk($old, $new, $field);

                continue;
            }

            if ($hasBefore && $hasAfter && $old === $new) {
                continue;
            }

            yield new Change($field, $old, $new, $hasBefore, $hasAfter);
        }
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function isMap(array $value): bool
    {
        return $value === [] || !array_is_list($value);
    }
}
