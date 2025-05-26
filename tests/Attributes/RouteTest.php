<?php

namespace GCWorld\Routing\Tests\Attributes;

use GCWorld\Routing\Attributes\Route;
use GCWorld\Routing\Attributes\RouteMeta;
use GCWorld\Routing\Attributes\RoutePexCheck;
use GCWorld\Routing\Enumerations\RoutePexCheckType;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testGetRouteArrayWithAllParameters(): void
    {
        $meta = [
            new RouteMeta(key: 'description', value: 'Test Description'),
            new RouteMeta(key: 'keywords', value: 'test, phpunit'),
        ];
        $pex = [
            new RoutePexCheck(type: RoutePexCheckType::STANDARD, pexString: 'CAN_VIEW'),
            new RoutePexCheck(type: RoutePexCheckType::ANY, pexString: 'CAN_EDIT_ANY'),
            new RoutePexCheck(type: RoutePexCheckType::EXACT, pexString: 'CAN_DELETE_EXACT'),
            new RoutePexCheck(type: RoutePexCheckType::MAX, pexString: 'USER_MAX_LEVEL_10'),
            // Add another standard to test grouping
            new RoutePexCheck(type: RoutePexCheckType::STANDARD, pexString: 'IS_LOGGED_IN'),
        ];

        $route = new Route(
            autoWrapper: true,
            session: true,
            name: 'test_route',
            patterns: ['/test', '/sample/:id'],
            title: 'Test Route Title',
            meta: $meta,
            preArgs: ['pre1', 'pre2'],
            postArgs: ['post1'],
            pex: $pex
        );

        $expected = [
            'name'        => 'test_route',
            'autoWrapper' => true,
            'session'     => true,
            'title'       => 'Test Route Title',
            'preArgs'     => ['pre1', 'pre2'],
            'postArgs'    => ['post1'],
            'meta'        => [
                'description' => 'Test Description',
                'keywords'    => 'test, phpunit',
            ],
            'pexCheck'      => ['CAN_VIEW', 'IS_LOGGED_IN'],
            'pexCheckAny'   => ['CAN_EDIT_ANY'],
            'pexCheckExact' => ['CAN_DELETE_EXACT'],
            'pexCheckMax'   => ['USER_MAX_LEVEL_10'],
        ];
        // Note: 'patterns' is not part of getRouteArray output intentionally by design of Route::getRouteArray.
        $this->assertEquals($expected, $route->getRouteArray());
    }

    public function testGetRouteArrayWithDefaultParameters(): void
    {
        $route = new Route(name: 'default_route', patterns: ['/default']);

        $expected = [
            'name'        => 'default_route',
            'autoWrapper' => false, // Default
            'session'     => false, // Default
            'title'       => '',    // Default
            'preArgs'     => [],    // Default
            'postArgs'    => [],    // Default
            // 'meta' and pex keys should be absent if empty
        ];

        $this->assertEquals($expected, $route->getRouteArray());
    }

    public function testGetRouteArrayWithOnlySomePexTypes(): void
    {
        $pex = [
            new RoutePexCheck(type: RoutePexCheckType::STANDARD, pexString: 'PEX_A'),
            new RoutePexCheck(type: RoutePexCheckType::MAX, pexString: 'PEX_B'),
        ];
        $route = new Route(name: 'pex_route', pex: $pex);

        $expected = [
            'name'        => 'pex_route',
            'autoWrapper' => false,
            'session'     => false,
            'title'       => '',
            'preArgs'     => [],
            'postArgs'    => [],
            'pexCheck'    => ['PEX_A'],
            'pexCheckMax' => ['PEX_B'],
        ];
        $this->assertEquals($expected, $route->getRouteArray());
    }

    public function testGetRouteArrayWithEmptyMetaAndPex(): void
    {
        $route = new Route(name: 'empty_extra_route');

        $expected = [
            'name'        => 'empty_extra_route',
            'autoWrapper' => false,
            'session'     => false,
            'title'       => '',
            'preArgs'     => [],
            'postArgs'    => [],
        ];
        // Meta and Pex keys should not exist in the output array if they are empty.
        $this->assertEquals($expected, $route->getRouteArray());
        $routeArray = $route->getRouteArray();
        $this->assertArrayNotHasKey('meta', $routeArray);
        $this->assertArrayNotHasKey('pexCheck', $routeArray);
        $this->assertArrayNotHasKey('pexCheckAny', $routeArray);
        $this->assertArrayNotHasKey('pexCheckExact', $routeArray);
        $this->assertArrayNotHasKey('pexCheckMax', $routeArray);
    }
}
