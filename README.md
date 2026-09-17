# GCWorld Routing

GCWorld Routing is a PHP routing library for front-controller applications. It
discovers handler classes from PHP attributes or legacy docblocks, compiles
their definitions into PHP routing tables, dispatches requests by HTTP method,
and supports reverse routing, sessions, permission checks, hooks, and optional
Redis-backed route discovery.

The library provides routing and dispatch infrastructure. The consuming
application remains responsible for its front controller, dependency setup,
authentication model, response layout, and error rendering.

### Version
5.4.8

## Requirements

- PHP 8.4 or newer
- Composer 2
- The JSON and Redis PHP extensions
- Handler classes available through Composer autoloading

Database-backed route logging is optional and uses `gcworld/database`.

## Installation

Install the package with Composer:

```console
composer require gcworld/routing
```

## Basic usage

Define an autoloadable handler with a `Route` attribute. Extending
`AdvancedHandler` provides default implementations for supported HTTP methods
and causes the router to emit the string returned by the selected method:

```php
<?php

namespace App\Handler;

use GCWorld\Routing\Abstracts\AdvancedHandler;
use GCWorld\Routing\Attributes\Route;

#[Route(
    name: 'users_show',
    patterns: ['/users/:number'],
    title: 'User details',
)]
final class ShowUser extends AdvancedHandler
{
    public function get(): string
    {
        $userId = $this->args[0] ?? null;

        return 'User ' . $userId;
    }
}
```

Compile the handler definitions during application setup or deployment:

```php
use GCWorld\Routing\LoadRoutes;

LoadRoutes::getInstance()
    ->addPath(__DIR__ . '/src/Handler')
    ->generateRoutes(force: true);
```

The supplied path may be a PHP file or a directory. Discovered classes must be
autoloadable before compilation. Generated tables are written beneath
`src/Generated/` in the installed Routing package, so that location must be
writable while routes are compiled.

Dispatch the request from the application front controller:

```php
use GCWorld\Routing\Core\CoreRouter;

$router = CoreRouter::getInstance();
$router->forward();
```

`forward()` reads `PATH_INFO`, `ORIG_PATH_INFO`, or `REQUEST_URI` when no path
is supplied explicitly. It selects a route, constructs the handler with the
route arguments as one array, and invokes the lowercase request method such as
`get()`, `post()`, `put()`, or `delete()`.

## Route attributes

`Route` is repeatable, so one handler can expose multiple independently
configured route groups:

```php
use GCWorld\Routing\Abstracts\AdvancedHandler;
use GCWorld\Routing\Attributes\Route;
use GCWorld\Routing\Attributes\RouteMeta;
use GCWorld\Routing\Attributes\RoutePexCheck;
use GCWorld\Routing\Enumerations\RoutePexCheckType;

#[Route(
    autoWrapper: true,
    session: true,
    name: 'reports_show',
    patterns: ['/reports/:number', '/report/:number'],
    title: 'Report',
    meta: [
        new RouteMeta('section', 'reports'),
    ],
    preArgs: ['tenant'],
    postArgs: ['html'],
    pex: [
        new RoutePexCheck(RoutePexCheckType::STANDARD, 'reports.[1].view'),
    ],
)]
final class ShowReport extends AdvancedHandler
{
    // ...
}
```

The route options are:

| Option | Purpose |
| --- | --- |
| `name` | Stable name used for reverse routing |
| `patterns` | One or more paths handled by the class |
| `session` | Starts a PHP session before dispatch when no session is active |
| `autoWrapper` | Calls the handler's title and breadcrumb methods and updates a configured page wrapper |
| `title` | Route metadata available to the application |
| `meta` | Application-defined key/value metadata |
| `preArgs` | Values prepended to captured route arguments |
| `postArgs` | Values appended to captured route arguments |
| `pex` | Permission checks evaluated through the configured user class |

Pre- and post-arguments are included in both the handler constructor array and
the arguments passed to its request method. Permission placeholders such as
`[0]` are replaced from that final argument list.

## Route patterns

Static paths are matched directly. Dynamic paths use the following tokens:

| Token | Matches |
| --- | --- |
| `:single` | One ASCII letter or digit |
| `:combo` | Two ASCII letters or digits separated by `-` |
| `:number` | One or more digits |
| `:letter` | One or more ASCII letters |
| `:string` | One or more ASCII letters or digits |
| `:uuid` | A canonical hexadecimal UUID |
| `:alpha` | ASCII letters, digits, hyphens, and underscores |
| `:base64` | Base64 or Base64URL characters, including padding |
| `:base64url` | Base64URL characters without padding |
| `:anything` | Any non-slash path segment |
| `:consume` | The remainder of the path, including slashes |

Captured values retain their order and are available through
`CoreRouter::getFoundRouteArguments()` after dispatch.

## Legacy docblock routes

Existing handlers can continue to declare a route through docblock tags:

```php
/**
 * @router-pattern /reports/:number
 * @router-name reports_show
 * @router-session true
 * @router-autoWrapper true
 * @router-title Report
 * @router-meta section:reports
 * @router-preArgs tenant
 * @router-postArgs html
 * @router-pexCheck reports.[1].view
 */
final class ShowReport
{
    // ...
}
```

The supported permission tags are `router-pexCheck`, `router-pexCheckAny`,
`router-pexCheckExact`, and `router-pexCheckMax`. Repeating a tag produces a
list. Attributes are preferred for new handlers because they are typed and can
be repeated without numbered docblock groups.

## Reverse routing

Generate a URL from the declared route name and its dynamic values:

```php
$router = CoreRouter::getInstance();

$url = $router->reverse('users_show', [42]);
// /users/42
```

The compiler maintains separate reverse entries for routes with different
numbers of parameters. Callers use the declared name; the router selects the
entry from the number of values passed to `reverse()`.

`setRoutePrefix()` prepends an application path prefix, while `setBase()`
prepends a base URL. `reverseAll()` returns the complete compiled route data,
`reverseObject()` constructs the route's handler, and `reverseMe()` reverses
the route most recently dispatched by the router.

## Sessions and permission checks

When a route sets `session: true` and no PHP session is active, Routing fires
the session hooks, starts the session, and performs configured PEX checks. Set
the application user singleton before dispatch:

```php
$router->setUserClassName(\App\Security\CurrentUser::class);
$router->forward();
```

The class returned by `CurrentUser::getInstance()` must implement
`GCWorld\Interfaces\PEX`. Failed checks fire the `403_pex` hook; the
application is responsible for registering a hook that renders or terminates
the denied request. Session and authorization policy remain application
responsibilities.

## Handler output and XHR requests

`AdvancedHandler` returns strings and supports all HTTP and corresponding XHR
method names. Its default methods throw `RouterException404`, so handlers only
need to override the methods they support.

`JSONHandler` defines XHR methods returning arrays. Routing selects an XHR
method when the request includes
`HTTP_X_REQUESTED_WITH=XMLHttpRequest`, when the `gc_ajax` request value is
truthy, or whenever the handler implements `JSONHandlerInterface`. JSON handler
results are encoded automatically.

With `autoWrapper: true`, handlers implementing `HandlerInterface` or
`AdvancedHandlerInterface` have `setBreadcrumbs()` and `getTitle()` called
before their request method. Configure a singleton page-wrapper class that
implements `GCWorld\Interfaces\PageWrapper` to receive the title:

```php
$router->setPageWrapperName(\App\View\PageWrapper::class);
```

## Hooks and router exceptions

Hooks are registered as static callable strings:

```php
$router->addHook('404', \App\Routing\ErrorHandler::class . '::notFound');
$router->addHook('403_pex', \App\Routing\ErrorHandler::class . '::denied');
```

Available lifecycle hooks are:

- `before_request`
- `pre-session_start` and `post-session_start`
- `before_handler` and `after_handler`
- `before_request_method` and `after_request_method`
- `after_output` and `after_request`
- `403`, `403_pex`, `404`, and `custom`

Handler constructors and request methods may throw exceptions implementing
`RouterExceptionInterface`. The included 403, 404, custom-response, PEX, and
redirect exceptions translate themselves into hooks, response codes, or
headers. Applications must attach the corresponding hooks when they expect
rendered error output.

## Optional caching and diagnostics

Attach a Redis connection to cache discovered paths:

```php
$router->attachRedisCache($redis);
```

`RouteDiscovery::purgeRedis()` clears the cache for its router name. Route
generation can also receive Redis and `gcworld/database` connections through
`LoadRoutes::attachRedisCache()` and `LoadRoutes::attachDatabase()`.

`forceRoutes()` supplies an in-memory forward table and is useful for tests or
specialized bootstrapping. `discoverRoute()` resolves a path without invoking
its handler and returns `RouteDiscoveryData` containing the matched pattern,
handler configuration, and captured values.

## Local development

The supported local environment uses the public KongHack PHP 8.4 image:

```console
./dc up -d
./dc exec php composer install
./dc exec php composer check
./dc down
```

The committed Compose configuration mounts only this repository. It does not
expose host SSH keys or Composer credentials. Developers who require private
Composer authentication can copy `docker-compose.override.yml.example` to the
ignored `docker-compose.override.yml`; that override exposes credentials to
container processes and should only be enabled when needed.

Individual quality commands are also available:

```console
./dc exec php composer lint
./dc exec php composer phpstan
./dc exec php composer phpcs
./dc exec php composer test
```

PHPStan is enforced at level 5. PHPCS enforces PSR-12 errors; advisory warnings
such as line length do not fail the build. GitHub Actions runs the complete
suite on PHP 8.4 and 8.5.

## Releases

Releases use bare semantic-version tags such as `5.4.8`. Before tagging a
release:

1. Add release notes under the matching version heading in `CHANGELOG.md`.
2. Update `VERSION` and the value immediately below `### Version` in this file.
3. Push the release commit and matching tag.

GitHub Actions validates the release metadata and complete PHP quality matrix
before creating a GitHub Release from `CHANGELOG.md`. Release tags must not be
moved or reused.

## License

GCWorld Routing is open-source software licensed under the MIT License.
