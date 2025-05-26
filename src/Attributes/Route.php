<?php
namespace GCWorld\Routing\Attributes;

use Attribute;
use GCWorld\Routing\Enumerations\RoutePexCheckType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    /**
     * @param bool $autoWrapper
     * @param bool $session
     * @param string $name
     * @param array<int, string> $patterns  // Kept as array as per specific instruction, though list<string> might fit
     * @param string $title
     * @param array<int, RouteMeta> $meta
     * @param array<int, string> $preArgs
     * @param array<int, string> $postArgs
     * @param array<int, RoutePexCheck> $pex
     */
    public function __construct(
        public readonly bool $autoWrapper = false,
        public readonly bool $session     = false,
        public readonly string $name      = '',
        public readonly array  $patterns  = [],
        public readonly string $title     = '',
        /** @var array<int, RouteMeta> */
        public readonly array  $meta      = [],
        /** @var array<int, string> */
        public readonly array  $preArgs   = [],
        /** @var array<int, string> */
        public readonly array  $postArgs  = [],
        /** @var array<int, RoutePexCheck> */
        public readonly array  $pex       = [],
    ) {

    }

    /**
     * @return array
     */
    public function getRouteArray(): array
    {
        $output = [
            'name'        => $this->name,
            'autoWrapper' => $this->autoWrapper,
            'session'     => $this->session,
            'title'       => $this->title,
            'preArgs'     => $this->preArgs,
            'postArgs'    => $this->postArgs,
        ];

        if(!empty($this->meta)) {
            $output['meta'] = [];
            foreach($this->meta as $meta) {
                $output['meta'][$meta->key] = $meta->value;
            }
        }
        foreach($this->pex as $pex) {
            $key = match($pex->type) {
                RoutePexCheckType::STANDARD => 'pexCheck',
                RoutePexCheckType::ANY      => 'pexCheckAny',
                RoutePexCheckType::EXACT    => 'pexCheckExact',
                RoutePexCheckType::MAX      => 'pexCheckMax',
            };
            if(!isset($output[$key])) {
                $output[$key] = [];
            }
            $output[$key][] = $pex->pexString;
        }

        return $output;
    }
}
