<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\ProdAssert\ProdAssert;
use MyFinances\Util\StaticClassTrait;

use function array_key_exists;
use function count;

final class ContextUtil
{
    use StaticClassTrait;

    /**
     * @param array<array-key, mixed> $localCtx
     * @param array<array-key, mixed> $externalCtx
     *
     * @return array<array-key, mixed>
     */
    public static function merge(array $localCtx, array $externalCtx): array
    {
        $result = $localCtx;
        foreach ($externalCtx as $key => $value) {
            $adaptedKey = array_key_exists($key, $result) ? self::adaptExternalKeyForMerge($localCtx, $key) : $key;
            $result[$adaptedKey] = $value;
        }
        return $result;
    }

    /**
     * @param array<array-key, mixed> $localCtx
     */
    private static function adaptExternalKeyForMerge(array $localCtx, string $externalKey): string
    {
        $maxCount = count($localCtx) + 1;
        for ($suffixCount = 1; $suffixCount <= $maxCount; ++$suffixCount) {
            $adaptedKey = $externalKey . 'external' . ($suffixCount === 1 ? '' : (' ' . $suffixCount));
            if (!array_key_exists($adaptedKey, $localCtx)) {
                return $adaptedKey;
            }
        }

        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);
        $assert->o1()?->unreachable();
        return 'DEFAULT FALLBACK KEY';
    }
}
