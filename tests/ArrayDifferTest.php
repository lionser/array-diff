<?php

declare(strict_types=1);

namespace Lionser\ArrayDiff\Tests;

use InvalidArgumentException;
use Lionser\ArrayDiff\ArrayDiffer;
use Lionser\ArrayDiff\Change;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArrayDifferTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $before
     * @param array<array-key, mixed> $after
     * @param list<array{string, mixed, mixed}> $expected
     */
    #[DataProvider('cases')]
    public function testDiff(array $before, array $after, array $expected): void
    {
        $actual = array_map(
            static fn (Change $change): array => [$change->field, $change->old, $change->new],
            (new ArrayDiffer())->diff($before, $after),
        );

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, array<array-key, mixed>, list<array{string, mixed, mixed}>}>
     */
    public static function cases(): iterable
    {
        yield 'identical' => [
            ['a' => ['b' => 1]],
            ['a' => ['b' => 1]],
            [],
        ];

        yield 'deep scalar changed' => [
            ['a' => ['b' => ['c' => 1, 'd' => 2]]],
            ['a' => ['b' => ['c' => 3, 'd' => 2]]],
            [['a.b.c', 1, 3]],
        ];

        yield 'key added' => [
            ['a' => 1],
            ['a' => 1, 'b' => 2],
            [['b', null, 2]],
        ];

        yield 'key removed' => [
            ['a' => 1, 'b' => 2],
            ['a' => 1],
            [['b', 2, null]],
        ];

        yield 'subtree added as a whole' => [
            [],
            ['a' => ['x' => 1]],
            [['a', null, ['x' => 1]]],
        ];

        yield 'subtree removed as a whole' => [
            ['a' => ['x' => 1]],
            [],
            [['a', ['x' => 1], null]],
        ];

        yield 'list is atomic' => [
            ['tags' => ['a', 'b']],
            ['tags' => ['a', 'c']],
            [['tags', ['a', 'b'], ['a', 'c']]],
        ];

        yield 'equal lists' => [
            ['tags' => ['a', 'b']],
            ['tags' => ['a', 'b']],
            [],
        ];

        yield 'map to list' => [
            ['x' => ['a' => 1]],
            ['x' => [1, 2]],
            [['x', ['a' => 1], [1, 2]]],
        ];

        yield 'list to map' => [
            ['x' => [1, 2]],
            ['x' => ['a' => 1]],
            [['x', [1, 2], ['a' => 1]]],
        ];

        yield 'map to scalar' => [
            ['x' => ['a' => 1]],
            ['x' => 5],
            [['x', ['a' => 1], 5]],
        ];

        yield 'scalar to map' => [
            ['x' => 5],
            ['x' => ['a' => 1]],
            [['x', 5, ['a' => 1]]],
        ];

        yield 'empty array to map' => [
            ['x' => []],
            ['x' => ['a' => 1]],
            [['x.a', null, 1]],
        ];

        yield 'map to empty array' => [
            ['x' => ['a' => 1]],
            ['x' => []],
            [['x.a', 1, null]],
        ];

        yield 'empty arrays on both sides' => [
            ['x' => []],
            ['x' => []],
            [],
        ];

        yield 'empty array to list' => [
            ['x' => []],
            ['x' => [1, 2]],
            [['x', [], [1, 2]]],
        ];

        yield 'strict comparison' => [
            ['a' => 1],
            ['a' => '1'],
            [['a', 1, '1']],
        ];

        yield 'numeric keys' => [
            [1 => ['a' => 1]],
            [1 => ['a' => 2]],
            [['1.a', 1, 2]],
        ];

        yield 'order follows before then after' => [
            ['b' => 1, 'a' => 1],
            ['a' => 2, 'b' => 2, 'c' => 3],
            [['b', 1, 2], ['a', 1, 2], ['c', null, 3]],
        ];
    }

    public function testNullValueIsDistinguishedFromMissingKey(): void
    {
        $differ = new ArrayDiffer();

        $removed = $differ->diff(['a' => null], []);
        self::assertCount(1, $removed);
        self::assertTrue($removed[0]->hadOld);
        self::assertFalse($removed[0]->hasNew);

        $added = $differ->diff([], ['a' => null]);
        self::assertCount(1, $added);
        self::assertFalse($added[0]->hadOld);
        self::assertTrue($added[0]->hasNew);

        self::assertSame([], $differ->diff(['a' => null], ['a' => null]));
    }

    public function testCustomSeparatorResolvesDottedKeys(): void
    {
        $changes = (new ArrayDiffer('/'))->diff(
            ['a.b' => ['c' => 1]],
            ['a.b' => ['c' => 2]],
        );

        self::assertCount(1, $changes);
        self::assertSame('a.b/c', $changes[0]->field);
    }

    public function testDiffToArray(): void
    {
        self::assertSame(
            [['field' => 'a.b', 'old' => 1, 'new' => 2]],
            (new ArrayDiffer())->diffToArray(['a' => ['b' => 1]], ['a' => ['b' => 2]]),
        );
    }

    public function testEmptySeparatorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ArrayDiffer('');
    }
}
