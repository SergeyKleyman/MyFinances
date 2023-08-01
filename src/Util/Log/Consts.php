<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace MyFinances\Util\Log;

final class Consts
{
    public const TYPE_KEY = 'type';

    public const RESOURCE_TYPE_KEY = 'resource_type';
    public const RESOURCE_ID_KEY = 'resource_ID';
    public const RESOURCE_TYPE_VALUE = 'resource';

    public const VALUE_AS_STRING_KEY = 'value_as_string';
    public const VALUE_AS_DEBUG_INFO_KEY = 'value_as_string';

    public const SMALL_LIST_ARRAY_MAX_COUNT = 100;
    public const SMALL_MAP_ARRAY_MAX_COUNT = 100;

    public const LIST_ARRAY_TYPE_VALUE = 'list-array';
    public const MAP_ARRAY_TYPE_VALUE = 'map-array';
    public const ARRAY_COUNT_KEY = 'count';

    public const OBJECT_ID_KEY = 'object_ID';
    public const OBJECT_HASH_KEY = 'object_hash';

    public const MAX_DEPTH_REACHED = 'Max depth reached';
}
