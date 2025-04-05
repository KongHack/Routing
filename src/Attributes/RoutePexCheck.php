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
        public RoutePexCheckType $type,
        public string $pexString
    ) {

    }
}
