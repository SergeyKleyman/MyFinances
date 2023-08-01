<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

final class Logger implements LoggableInterface
{
    public Level $levelThreshold;

    public function __construct(
        public readonly string $srcCodeNamespace,
        public readonly string $srcCodeClass,
        public readonly string $srcCodeFunc,
        public readonly string $srcCodeFile,
    ) {
        Backend::singletonInstance()->addToTracked($this);
    }

    public function ifLevelEnabled(Level $level): ?EnabledProxy
    {
        return $level->isBelowThreshold($this->levelThreshold) ? new EnabledProxy($this, $level) : null;
    }

    public function critical(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::critical);
    }

    public function error(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::error);
    }

    public function warning(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::warning);
    }

    public function info(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::info);
    }

    public function debug(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::debug);
    }

    public function trace(): ?EnabledProxy
    {
        return $this->ifLevelEnabled(Level::trace);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->write(['level' => $this->levelThreshold->name]);
    }
}
