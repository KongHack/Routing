<?php
namespace GCWorld\Routing\Core;

/**
 * Class RouteDiscoveryData.
 */
class RouteDiscoveryData
{
    public readonly string $pattern;
    public readonly array  $handler;
    public readonly array  $matches;

    /**
     * @param string $pattern
     * @param array<string, mixed>  $handler  // Handler definition, typically from Route::getRouteArray()
     * @param array<array-key, string|int>  $matches  // Regex matches
     */
    public function __construct(string $pattern, array $handler, array $matches = [])
    {
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->matches = $matches;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array
     */
    public function getHandler(): array
    {
        return $this->handler;
    }

    /**
     * @return array
     */
    public function getMatches(): array
    {
        return $this->matches;
    }
}