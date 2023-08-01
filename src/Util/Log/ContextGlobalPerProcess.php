<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\ProdAssert\ProdAssert;
use MyFinances\Util\SingletonInstanceTrait;

final class ContextGlobalPerProcess
{
    use SingletonInstanceTrait;

    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function initName(string $name): void
    {
        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);
        $assert->o1()?->isNull($this->name);

        $this->name = $name;
    }
}
