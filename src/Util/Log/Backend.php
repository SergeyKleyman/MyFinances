<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\SingletonInstanceTrait;

final class Backend
{
    use SingletonInstanceTrait;

    private Level $levelThreshold = Level::info;

    private SinkInterface $sink;

    /** @var Logger[] */
    private array $loggers = [];

    /**
     * Constructor is hidden
     *
     * @noinspection PhpUnused
     */
    private function __construct()
    {
        $this->sink = Sink::singletonInstance();
    }

    public function getLevelThreshold(): Level
    {
        return $this->levelThreshold;
    }

    public function setLevelThreshold(Level $levelThreshold): void
    {
        $this->levelThreshold = $levelThreshold;
        foreach ($this->loggers as $logger) {
            $logger->levelThreshold = $levelThreshold;
        }
    }

    public function getSink(): SinkInterface
    {
        return $this->sink;
    }

    public function setSink(SinkInterface $sink): void
    {
        $this->sink = $sink;
    }

    public function addToTracked(Logger $logger): void
    {
        $logger->levelThreshold = $this->levelThreshold;
        if (!in_array($logger, $this->loggers, /* strict */ true)) {
            $this->loggers[] = $logger;
        }
    }

    public function log(Record $statement): void
    {
        $this->sink->consume($statement);
    }
}
