<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Exceptions;

use GCWorld\Routing\Exceptions\RouterExceptionRedirect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class RouterExceptionRedirectTest extends TestCase
{
    private string $requestMethod = '';

    protected function setUp(): void
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    }

    protected function tearDown(): void
    {
        if ($this->requestMethod === '') {
            unset($_SERVER['REQUEST_METHOD']);
            return;
        }

        $_SERVER['REQUEST_METHOD'] = $this->requestMethod;
    }

    #[DataProvider('defaultStatusCodeProvider')]
    public function testChoosesDefaultStatusCodeForRequestMethod(string $method, int $expected): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $exception = new RouterExceptionRedirect('/target');

        self::assertSame($expected, $this->statusCode($exception));
    }

    /** @return iterable<string, array{string, int}> */
    public static function defaultStatusCodeProvider(): iterable
    {
        yield 'GET is permanent' => ['GET', 301];
        yield 'POST is temporary' => ['POST', 302];
    }

    public function testExplicitStatusCodeWins(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $exception = new RouterExceptionRedirect('/target', code: 307);

        self::assertSame(307, $this->statusCode($exception));
    }

    private function statusCode(RouterExceptionRedirect $exception): int
    {
        $property = new ReflectionProperty($exception, 'statusCode');

        return $property->getValue($exception);
    }
}
