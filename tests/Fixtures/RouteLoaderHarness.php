<?php

declare(strict_types=1);

namespace GCWorld\Routing\Tests\Fixtures;

use GCWorld\Routing\LoadRoutes;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;

final class RouteLoaderHarness extends LoadRoutes
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function discoverPath(string $path): array
    {
        $this->setLint(false);
        $this->addPath($path);

        return $this->generateAnnotatedRoutes();
    }

    /**
     * @param class-string $className
     * @return array<string, array<string, mixed>>|null
     */
    public function compileDocBlock(string $className): ?array
    {
        $comment = (new ReflectionClass($className))->getDocComment();
        if ($comment === false) {
            return null;
        }

        return $this->processTags($className, DocBlockFactory::createInstance()->create($comment));
    }

    /**
     * @param class-string $className
     * @return array<string, array<string, mixed>>|null
     */
    public function compileAttributes(string $className): ?array
    {
        $attributes = (new ReflectionClass($className))->getAttributes();

        return $this->processAttributes($className, $attributes);
    }
}
