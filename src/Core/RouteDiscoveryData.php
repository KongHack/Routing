<?php
namespace GCWorld\Routing\Core;

/**
 * Class RouteDiscoveryData.
 */
class RouteDiscoveryData
{
    protected string $pattern;
    protected array  $handler;
    protected array  $matches;

    /**
     * @param string $pattern
     * @param array  $handler
     * @param array  $matches
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