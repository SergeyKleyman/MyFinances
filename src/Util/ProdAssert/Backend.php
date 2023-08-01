<?php

declare(strict_types=1);

namespace MyFinances\Util\ProdAssert;

use MyFinances\Util\SingletonInstanceTrait;

final class Backend
{
    use SingletonInstanceTrait;

    private Level $levelThreshold = Level::O1;

    /** @var ProdAssert[] */
    private array $asserts = [];

    public function setLevelThreshold(Level $levelThreshold): void
    {
        $this->levelThreshold = $levelThreshold;
        foreach ($this->asserts as $assert) {
            $assert->levelThreshold = $levelThreshold;
        }
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function addToTracked(string $namespace, string $class, string $func, ProdAssert $assert): void
    {
        $assert->levelThreshold = $this->levelThreshold;
        if (!in_array($assert, $this->asserts, /* strict */ true)) {
            $this->asserts[] = $assert;
        }
    }
}
