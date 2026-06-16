<?php

namespace Tests\Unit;

use App\Domain\TreeReward;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the domain rule — no database, no Laravel bootstrap.
 * Extends PHPUnit's TestCase (not Tests\TestCase) on purpose.
 */
class TreeRewardTest extends TestCase
{
    #[DataProvider('treesCases')]
    public function test_trees_for(int $perTree, int $visits, int $expected): void
    {
        $this->assertSame($expected, (new TreeReward($perTree))->treesFor($visits));
    }

    public static function treesCases(): array
    {
        return [
            'no visits' => [5, 0, 0],
            'below threshold' => [5, 4, 0],
            'exactly at threshold' => [5, 5, 1],
            'exact multiple' => [5, 10, 2],
            'between multiples' => [5, 7, 1],
            'x of one plants every visit' => [1, 3, 3],
        ];
    }

    #[DataProvider('plantedCases')]
    public function test_planted_between(int $perTree, int $before, int $after, bool $expected): void
    {
        $this->assertSame($expected, (new TreeReward($perTree))->plantedBetween($before, $after));
    }

    public static function plantedCases(): array
    {
        return [
            'crosses a boundary' => [5, 4, 5, true],
            'within the same bucket' => [5, 1, 2, false],
            'after the boundary' => [5, 5, 6, false],
            'lands on another multiple' => [5, 9, 10, true],
        ];
    }

    #[DataProvider('invalidPerTree')]
    public function test_rejects_invalid_visits_per_tree(int $perTree): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TreeReward($perTree);
    }

    public static function invalidPerTree(): array
    {
        return ['zero' => [0], 'negative' => [-1]];
    }
}
