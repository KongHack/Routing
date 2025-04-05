<?php
namespace GCWorld\Routing\Attributes;

use Attribute;
use GCWorld\Routing\Enumerations\RoutePexCheckType;

#[Attribute]
class Route
{
    /**
     * @param bool $autoWrapper
     * @param bool $session
     * @param string $name
     * @param array $patterns
     * @param string $title
     * @param RouteMeta[] $meta
     * @param string[] $preArgs
     * @param string[] $postArgs
     * @param RoutePexCheck[] $pex
     */
    public function __construct(
        public bool $autoWrapper = false,
        public bool $session     = false,
        public string $name      = '',
        public array  $patterns  = [],
        public string $title     = '',
        public array  $meta      = [],
        public array  $preArgs   = [],
        public array  $postArgs  = [],
        public array  $pex       = [],
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
