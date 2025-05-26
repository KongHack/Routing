<?php
namespace GCWorld\Routing\Attributes;

use Attribute;
use GCWorld\Routing\Enumerations\RoutePexCheckType;

#[Attribute]
class RoutePexCheck
{
    /**
     * @param RoutePexCheckType $type
     * @param string $pexString
     */
    public function __construct(
        public readonly RoutePexCheckType $type,
        public readonly string $pexString
    ) {

    }
}
