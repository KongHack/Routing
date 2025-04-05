<?php
namespace Test;

use GCWorld\Routing\Attributes\Route;
use GCWorld\Routing\Attributes\RouteMeta;

#[Route(
    autoWrapper: true,
    session: true,
    name: 'test_route',
    patterns: [
        '/test'
    ],
    meta: [
        new RouteMeta(key: 'leftSide', value: 'rightSide'),
        new RouteMeta(key: 'meta2', value: 'hereItIs')
    ]
)]
class TestAttributes
{

}
