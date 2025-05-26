# PHP 8.4 Enhancements

## Target PHP Version

This routing library is developed with PHP 8.4 in mind, aiming to leverage its features for improved performance, type safety, and code clarity. While it may maintain compatibility with slightly older PHP 8 versions, the codebase is progressively being optimized for PHP 8.4.

## Internal Enhancements

Several internal enhancements have been implemented, primarily focusing on leveraging modern PHP features. These changes are mostly transparent to the end-user of the library but contribute to its robustness, maintainability, and potential performance gains.

### 1. `readonly` Properties

*   **Concept**: PHP 8.1 introduced `readonly` properties, which can only be initialized once (typically in the constructor) and cannot be changed thereafter. This enforces immutability. PHP 8.3 further refined this by allowing re-initialization during cloning.
*   **Application in Library**:
    *   Many Data Transfer Object (DTO) style classes and configuration holders now use `readonly` properties. This ensures that once these objects are configured (e.g., a `Route` attribute's parameters), their state remains consistent throughout their lifecycle.
    *   Examples of classes using `readonly` properties include:
        *   `GCWorld\Routing\Attributes\Route`
        *   `GCWorld\Routing\Attributes\RouteMeta`
        *   `GCWorld\Routing\Attributes\RoutePexCheck`
        *   `GCWorld\Routing\Core\RouteDiscoveryData`
        *   `GCWorld\Routing\Debugger` (for properties like database connection and storage path)
        *   `GCWorld\Routing\Processor` (for properties like its name and storage path)
*   **Benefits**:
    *   **Immutability**: Guarantees that the properties of these objects do not change unexpectedly after instantiation.
    *   **Predictability**: Makes the code easier to reason about, as the state of these objects is fixed.
    *   **Potential Performance**: While micro-optimizations, readonly properties can sometimes allow the PHP engine to make better optimizations.

### 2. Improved Type Safety with Specific Array Types

*   **Concept**: PHP's type system, especially with PHPDoc annotations and (where applicable in the future) native typed arrays/generics, allows for more precise definitions of array structures.
*   **Application in Library**:
    *   Docblocks (`@var`, `@param`, `@return`) have been updated to use more specific array type hints where possible.
    *   Examples include:
        *   `list<string>`: For simple, sequentially indexed arrays of strings (e.g., a list of master route names in `Processor`).
        *   `array<string, static>`: For associative arrays mapping string keys to instances of the current class (e.g., `CoreRouter::$instances`).
        *   `array<int, RouteMeta>`: For numerically indexed arrays containing `RouteMeta` objects.
        *   `array<string, array<string, mixed>>`: For associative arrays where keys are strings and values are themselves associative arrays with mixed content (e.g., `Processor::$routes_straight`).
*   **Benefits**:
    *   **Clarity**: Makes the expected structure of arrays clear to developers, improving code understanding.
    *   **Early Error Detection**: Helps static analysis tools (like Psalm, PHPStan) and IDEs to identify type mismatches and potential bugs earlier in the development process.
    *   **Maintainability**: More explicit types make the codebase easier to refactor and maintain.

## Impact on Public API

These enhancements are **mostly internal**. They are designed to improve the internal quality and robustness of the library by leveraging modern PHP features. The public API of the library (how you define routes, how you use `CoreRouter`, etc.) remains unchanged by these specific optimizations. The goal is to provide a more stable and maintainable library without requiring users to change their existing integration code.

---

See also:
*   [Overall Routing Mechanism](index.md)
*   [Route Attribute Documentation](route-attribute.md) (attributes now use readonly properties)
