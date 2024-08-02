<?php

declare(strict_types=1);

namespace App\Tests\fixtures;

final class TermTestFixtures extends AbstractTestFixtures implements TestFixtures
{
    public static function partial(): self
    {
        return new self([
            'taxonomy' => 'category',
            'term' => 'Test Term',
            'description' => 'Test Term Description',
        ]);
    }

    public static function complete(): array
    {
        return [];
    }
}
