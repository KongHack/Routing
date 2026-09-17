<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Fixtures;

/**
 * @router-pattern /reports/:number
 * @router-name reports_show
 * @router-session yes
 * @router-autoWrapper true
 * @router-title Report
 * @router-meta section:reports
 * @router-preArgs tenant
 * @router-postArgs format
 */
final class AnnotatedHandler
{
}
