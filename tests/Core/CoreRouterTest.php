<?php

namespace GCWorld\Routing\Tests\Core;

use GCWorld\Routing\Core\CoreRouter;
// Removed: use GCWorld\Routing\Core\RouteDiscoveryData;
// Removed: use GCWorld\Routing\Exceptions\ReverseRouteNotFoundException;
// Removed: use GCWorld\Routing\Exceptions\RouteClassNotFoundException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
// Removed: use FilesystemIterator;
// Removed: use RuntimeException;

/**
 * CoreRouterTest Test Suite.
 *
 * NOTE: Methods such as `reverse()`, `reverseAll()`, `discoverRoute()`, and `forward()`
 * are not unit tested here. These methods exhibit tight coupling with the file system
 * (e.g., dynamic loading and inclusion of generated `MasterRoute_*.php` files from
 * the `src/Generated` directory) and involve direct instantiation of internal
 * dependencies (e.g., `new RouteDiscovery()`).
 *
 * Without modifications to the source code of `CoreRouter.php` to allow for
 * dependency injection (e.g., for `RouteDiscovery` or a `RoutesInterface` loader/factory)
 * or other test seams, creating isolated unit tests for these methods is impractical.
 * Such tests would become integration tests requiring a complex setup of the generated
 * file structure, which was attempted but proved unreliable, especially with PHPUnit's
 * separate process execution model.
 */
class CoreRouterTest extends TestCase
{
    private const TEST_INSTANCE_NAME = 'PHPUNIT_CORE_ROUTER_TEST_INSTANCE';
    private ?CoreRouter $router = null;

    public static function setUpBeforeClass(): void
    {
        // Ensure vendor autoload is loaded, especially for separate processes if any test needs it.
        // (Currently, remaining tests are simple and may not strictly need separate process)
        require_once __DIR__ . '/../../vendor/autoload.php';
    }

    protected function setUp(): void
    {
        // Clear static instances from CoreRouter to ensure a fresh one for each test.
        $reflection = new ReflectionClass(CoreRouter::class);
        $instancesProperty = $reflection->getProperty('instances');
        $instancesProperty->setValue(null, []); 

        $this->router = CoreRouter::getInstance(self::TEST_INSTANCE_NAME);
        $_SERVER = []; // Clear server variables for tests like isXHRRequest
    }

    protected function tearDown(): void
    {
        // Reset CoreRouter internal static instances again to be absolutely sure for next test.
        $reflection = new ReflectionClass(CoreRouter::class);
        $instancesProperty = $reflection->getProperty('instances');
        $instancesProperty->setValue(null, []); 

        unset($this->router);
        $_SERVER = [];
    }

    // --- Start of actual tests ---

    public function testIsXHRRequestWhenHeaderIsSet(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->router->isXHRRequest());
    }

    public function testIsXHRRequestWhenHeaderIsNotSet(): void
    {
        $this->assertFalse($this->router->isXHRRequest());
    }
    
    public function testGetName(): void
    {
        $this->assertEquals(self::TEST_INSTANCE_NAME, $this->router->getName());
    }

    public function testSetAndGetRoutePrefix(): void
    {
        $this->assertNull($this->router->getRoutePrefix(), "Default route prefix should be null.");
        $this->router->setRoutePrefix('/api');
        $this->assertEquals('/api', $this->router->getRoutePrefix(), "Route prefix should be set to /api.");
        $this->router->setRoutePrefix(null); // Reset
        $this->assertNull($this->router->getRoutePrefix(), "Route prefix should be reset to null.");
    }

    public function testSetUserClassName(): void
    {
        $className = 'MyUser\\ClassName';
        $this->router->setUserClassName($className);
        
        // No direct getter, but we can infer it's set by lack of errors.
        // This is a limited test, as full functionality would require PEX checks.
        $reflection = new ReflectionClass(CoreRouter::class);
        $userClassNameProp = $reflection->getProperty('userClassName');
        $this->assertEquals($className, $userClassNameProp->getValue($this->router));
    }

    public function testSetPageWrapperName(): void
    {
        $className = 'MyWrapper\\ClassName';
        $this->router->setPageWrapperName($className);
        $this->assertEquals($className, $this->router->getPageWrapperName());
    }
    
    public function testGetCallingMethodDefaultIsEmpty(): void
    {
        $this->assertEmpty($this->router->getCallingMethod(), "Default calling method should be empty.");
    }

    // Test for setBase is omitted as its effect is primarily on reverse() which is not tested.
    // Tests for attachDebugger, forceRoutes, attachRedisCache are omitted as they primarily
    // set internal properties or interact with systems (Debugger, Redis) whose setup is
    // beyond simple unit testing scope here without more DI.
}
