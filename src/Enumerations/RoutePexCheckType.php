<?php
namespace GCWorld\Routing\Enumerations;

/**
 * RoutePexCheckType Enumeration
 */
enum RoutePexCheckType
{
    case STANDARD;
    case ANY;
    case MAX;
    case EXACT;
}