<?php

declare(strict_types=1);

namespace MyFinances\Util\ProdAssert;

final class ProdAssert
{
    public Level $levelThreshold;

    public function __construct(string $namespace, string $class, string $func)
    {
        Backend::singletonInstance()->addToTracked($namespace, $class, $func, $this);
    }

    public function ifLevelEnabled(Level $level): ?EnabledProxy
    {
        return $level->isBelowThreshold($this->levelThreshold) ? EnabledProxy::singletonInstance() : null;
    }

    public function o1(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::O1);
    }

    public function oN(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::On);
    }

    public function oN2(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::On2);
    }
}
