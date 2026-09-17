<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests;

use GCWorld\Routing\Hook;
use PHPUnit\Framework\TestCase;

final class HookTest extends TestCase
{
    /** @var list<array<int, mixed>> */
    private static array $calls = [];

    protected function setUp(): void
    {
        self::$calls = [];
    }

    public function testFireCallsRegisteredHooksWithParameters(): void
    {
        $instance = 'test-' . uniqid();
        Hook::add($instance, 'before_request', self::class . '::record');
        Hook::add($instance, 'before_request', self::class . '::record');

        Hook::fire($instance, 'before_request', ['alpha', 42]);

        self::assertSame([['alpha', 42], ['alpha', 42]], self::$calls);
    }

    public function testUnknownHookDoesNothing(): void
    {
        Hook::fire('test-' . uniqid(), 'missing');

        self::assertSame([], self::$calls);
    }

    public static function record(mixed ...$arguments): void
    {
        self::$calls[] = $arguments;
    }
}
