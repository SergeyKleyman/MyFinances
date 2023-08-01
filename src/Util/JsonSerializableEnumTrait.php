<?php

declare(strict_types=1);

namespace MyFinances\Util;

trait JsonSerializableEnumTrait
{
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
