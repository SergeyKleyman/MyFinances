<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

interface SinkInterface
{
    /**
     * Dummy comment to use inheritDoc in the implementing classes
     */
    public function consume(Record $statement): void;
}
