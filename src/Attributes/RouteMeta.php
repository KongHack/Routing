<?php
namespace GCWorld\Routing\Attributes;

use Attribute;

#[Attribute]
class RouteMeta
{
    /**
     * @param string $key
     * @param string $value
     */
    public function __construct(
        public string $key,
        public string $value,
    ) {

    }
}