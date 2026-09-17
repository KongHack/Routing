<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Attributes;

use GCWorld\Routing\Attributes\Route;
use GCWorld\Routing\Attributes\RouteMeta;
use GCWorld\Routing\Attributes\RoutePexCheck;
use GCWorld\Routing\Enumerations\RoutePexCheckType;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testRouteConvertsItsConfigurationToAnArray(): void
    {
        $route = new Route(
            autoWrapper: true,
            session: true,
            name: 'users_show',
            patterns: ['/users/:number'],
            title: 'User details',
            meta: [
                new RouteMeta('section', 'users'),
                new RouteMeta('layout', 'wide'),
            ],
            preArgs: ['prefix'],
            postArgs: ['suffix'],
            pex: [
                new RoutePexCheck(RoutePexCheckType::STANDARD, 'users.view'),
                new RoutePexCheck(RoutePexCheckType::ANY, 'users.manage'),
                new RoutePexCheck(RoutePexCheckType::EXACT, 'users.owner'),
                new RoutePexCheck(RoutePexCheckType::MAX, 'users.admin'),
            ],
        );

        self::assertSame(
            [
                'name' => 'users_show',
                'autoWrapper' => true,
                'session' => true,
                'title' => 'User details',
                'preArgs' => ['prefix'],
                'postArgs' => ['suffix'],
                'meta' => ['section' => 'users', 'layout' => 'wide'],
                'pexCheck' => ['users.view'],
                'pexCheckAny' => ['users.manage'],
                'pexCheckExact' => ['users.owner'],
                'pexCheckMax' => ['users.admin'],
            ],
            $route->getRouteArray(),
        );
    }

    public function testOptionalCollectionsAreOmittedWhenEmpty(): void
    {
        $route = new Route(name: 'home', patterns: ['/']);

        self::assertSame(
            [
                'name' => 'home',
                'autoWrapper' => false,
                'session' => false,
                'title' => '',
                'preArgs' => [],
                'postArgs' => [],
            ],
            $route->getRouteArray(),
        );
    }
}
