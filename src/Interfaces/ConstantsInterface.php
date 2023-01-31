<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface ConstantsInterface.
 */
interface ConstantsInterface
{
    public const DEFAULT_NAME      = 'GCDefault';
    public const CLASS_MISC        = '\GCWorld\Routing\Generated\__NAME__\MasterRoute_MISC';
    public const CLASS_REPLACEMENT = '\GCWorld\Routing\Generated\__NAME__\MasterRoute_REPLACEMENT_KEY';
    public const CLASS_ROUTABLE    = '\GCWorld\Routing\Generated\__NAME__\MasterRoute_';
    public const ROUTING_TOKENS    = [
        ':single'   => '([a-zA-Z0-9]{1})',
        ':combo'    => '([a-zA-Z0-9]-[a-zA-Z0-9])',
        ':number'   => '([0-9]+)',
        ':letter'   => '([a-zA-Z]+)',
        ':string'   => '([a-zA-Z0-9]+)',
        ':uuid'     => '([a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12})',
        ':alpha'    => '([a-zA-Z0-9-_]+)',
        ':base64'   => '([a-zA-Z0-9-_\+\=]+)',
        ':anything' => '([^/]+)',
        ':consume'  => '(.+)',
    ];
}
