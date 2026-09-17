<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests;

use GCWorld\Routing\Processor;
use PHPUnit\Framework\TestCase;

final class ProcessorTest extends TestCase
{
    public function testCleansRouteSegmentsForGeneratedClassNames(): void
    {
        self::assertSame('USERSETTINGS', Processor::cleanClassName('user-settings'));
        self::assertSame('REPLACEMENT_KEY', Processor::cleanClassName(':number'));
    }
}
