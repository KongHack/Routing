# GCWorld PHP Routing Library

## Purpose

This library provides a flexible and powerful PHP routing system designed to handle incoming HTTP requests and dispatch them to the appropriate application logic. It supports modern PHP features, including PHP 8 Attributes, for defining routes, and includes a processing step to generate optimized route caches for high performance.

## Key Phases

The routing mechanism operates in two main phases:

### 1. Route Definition & Processing

This phase involves defining how URLs map to your application's code and then processing these definitions into an optimized format.

*   **Route Definitions:**
    *   **PHP 8 Attributes (Recommended):** The modern way to define routes is by using the `#[Route]` attribute directly on your handler classes. This provides a clean and co-located way to specify route properties. (See [`route-attribute.md`](route-attribute.md) for details).
    *   **`RawRoutesInterface`:** For more complex scenarios or integrating with other systems, routes can be defined by classes implementing `RawRoutesInterface`. These classes provide methods that return arrays of route definitions.
    *   **Legacy Annotations:** While PHP Attributes are preferred, the library might still have legacy support for annotation-based route definitions (though this is being phased out).

*   **Discovery (`LoadRoutes.php`):**
    *   A script, typically `LoadRoutes.php` (or a similar mechanism integrated into your framework), is responsible for scanning your codebase (specified directories) to find all route definitions (Attributes, `RawRoutesInterface` implementations).

*   **Processing (`Processor.php`):**
    *   Once routes are discovered, `GCWorld\Routing\Processor` takes these raw definitions.
    *   It compiles, validates, and organizes them.
    *   Crucially, it generates optimized PHP files (e.g., `MasterRoute_*.php` and `MasterRoute_MISC.php`) within the `src/Generated/` directory (or a configured similar path). These generated files contain arrays of processed route data, allowing for very fast lookups during runtime. This pre-processing step avoids the need to parse attributes or configuration files on every request.

### 2. Route Execution

This phase handles incoming HTTP requests using the processed route definitions.

*   **Main Entry Point (`CoreRouter.php`):**
    *   `GCWorld\Routing\Core\CoreRouter` is the central class for handling requests. It's typically instantiated as a singleton.
    *   It receives the incoming URI and uses `RouteDiscovery` to find a match.
    *   If a route is found, `CoreRouter` dispatches the request to the associated handler class and method.
    *   (See [`core-router.md`](core-router.md) for details).

*   **Route Discovery (`RouteDiscovery.php`):**
    *   `GCWorld\Routing\Core\RouteDiscovery` is responsible for taking a URI path and finding the corresponding pre-processed route definition from the generated `MasterRoute_*.php` files.
    *   It employs various matching strategies, including direct matches and regular expressions.
    *   It supports caching of resolved routes using Redis to further speed up lookups, especially for frequently accessed routes.
    *   (See [`route-discovery.md`](route-discovery.md) for details).

## Modern Approach: PHP 8 Attributes

The library encourages the use of PHP 8 Attributes (`#[Route]`, `#[RouteMeta]`, `#[RoutePexCheck]`) for defining routes. This method is cleaner, more readable, and allows route definitions to be directly associated with their handler classes, improving code organization and maintainability.

---

For more detailed information on specific components, please refer to their respective documentation pages.
