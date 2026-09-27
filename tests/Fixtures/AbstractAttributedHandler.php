<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Fixtures;

use GCWorld\Routing\Attributes\Route;

#[Route(
    name: 'abstract_handler',
    patterns: ['/abstract-handler'],
)]
abstract class AbstractAttributedHandler
{
}
