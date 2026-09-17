<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Fixtures;

use GCWorld\Routing\Attributes\Route;
use GCWorld\Routing\Attributes\RouteMeta;

#[Route(
    autoWrapper: true,
    name: 'dashboard',
    patterns: ['/dashboard', '/home'],
    meta: [new RouteMeta('section', 'dashboard')],
)]
final class AttributedHandler
{
}
