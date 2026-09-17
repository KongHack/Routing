<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests;

use GCWorld\Routing\Tests\Fixtures\AnnotatedHandler;
use GCWorld\Routing\Tests\Fixtures\AttributedHandler;
use GCWorld\Routing\Tests\Fixtures\RouteLoaderHarness;
use PHPUnit\Framework\TestCase;

final class LoadRoutesTest extends TestCase
{
    public function testCompilesRouteAttributesForEveryPattern(): void
    {
        $loader = RouteLoaderHarness::getInstance('attribute-test');

        self::assertSame(
            [
                '/dashboard' => [
                    'name' => 'dashboard',
                    'autoWrapper' => true,
                    'session' => false,
                    'title' => '',
                    'preArgs' => [],
                    'postArgs' => [],
                    'meta' => ['section' => 'dashboard'],
                    'class' => AttributedHandler::class,
                ],
                '/home' => [
                    'name' => 'dashboard',
                    'autoWrapper' => true,
                    'session' => false,
                    'title' => '',
                    'preArgs' => [],
                    'postArgs' => [],
                    'meta' => ['section' => 'dashboard'],
                    'class' => AttributedHandler::class,
                ],
            ],
            $loader->compileAttributes(AttributedHandler::class),
        );
    }

    public function testCompilesLegacyDocBlockRoutes(): void
    {
        $loader = RouteLoaderHarness::getInstance('docblock-test');

        self::assertSame(
            [
                '/reports/:number' => [
                    'class' => AnnotatedHandler::class,
                    'name' => 'reports_show',
                    'autoWrapper' => true,
                    'session' => true,
                    'preArgs' => ['tenant'],
                    'postArgs' => ['format'],
                    'title' => 'Report',
                    'meta' => ['section' => 'reports'],
                ],
            ],
            $loader->compileDocBlock(AnnotatedHandler::class),
        );
    }
}
