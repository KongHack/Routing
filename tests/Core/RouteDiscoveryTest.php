<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Core;

use GCWorld\Routing\Core\RouteDiscovery;
use PHPUnit\Framework\TestCase;

final class RouteDiscoveryTest extends TestCase
{
    public function testExactRouteTakesPriorityOverTokenRoute(): void
    {
        $discovery = new RouteDiscovery();
        $discovery->forceRoutes([
            '/users/:number' => ['name' => 'users_show'],
            '/users/new' => ['name' => 'users_new'],
        ]);

        $result = $discovery->execute('/users/new');

        self::assertNotNull($result);
        self::assertSame('/users/new', $result->getPattern());
        self::assertSame(['name' => 'users_new'], $result->getHandler());
        self::assertSame([], $result->getMatches());
    }

    public function testRouteTokensCaptureArguments(): void
    {
        $discovery = new RouteDiscovery();
        $discovery->forceRoutes([
            '/users/:number/files/:anything' => ['name' => 'users_file'],
        ]);

        $result = $discovery->execute('/users/42/files/avatar.png');

        self::assertNotNull($result);
        self::assertSame('/users/([0-9]+)/files/([^/]+)', $result->getPattern());
        self::assertSame(['42', 'avatar.png'], $result->getMatches());
    }

    public function testUnknownRouteReturnsNull(): void
    {
        $discovery = new RouteDiscovery();
        $discovery->forceRoutes(['/known' => ['name' => 'known']]);

        self::assertNull($discovery->execute('/unknown'));
    }
}
