<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use ReflectionClass;
use ReflectionException;

trait LoggableTrait
{
    protected static function classNameToLog(): ?string
    {
        return null;
    }

    /**
     * @return string[]
     */
    protected static function defaultPropertiesExcludedFromLog(): array
    {
        return ['logger'];
    }

    /**
     * @return string[]
     */
    protected static function propertiesExcludedFromLog(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $customPropValues
     */
    protected function toLogLoggableTraitImpl(LogStreamInterface $stream, array $customPropValues = []): void
    {
        $nameToValue = $customPropValues;

        $classNameToLog = static::classNameToLog();
        if ($classNameToLog !== null) {
            $nameToValue[Consts::TYPE_KEY] = $classNameToLog;
        }

        try {
            /** @throws ReflectionException */
            $currentClass = new ReflectionClass($this::class);
        } catch (ReflectionException $ex) { // @phpstan-ignore-line
            $stream->write(
                Subsystem::onInternalFailure('Failed to reflect', ['class' => $this::class], $ex)
            );
            return;
        }

        $propertiesExcludedFromLog = array_merge(static::propertiesExcludedFromLog(), static::defaultPropertiesExcludedFromLog());
        while (true) {
            foreach ($currentClass->getProperties() as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }

                $propName = $reflectionProperty->name;
                if (array_key_exists($propName, $customPropValues)) {
                    continue;
                }
                if (in_array($propName, $propertiesExcludedFromLog, /* strict */ true)) {
                    continue;
                }
                $nameToValue[$propName] = $reflectionProperty->getValue($this);
            }
            $currentClass = $currentClass->getParentClass();
            if ($currentClass === false) {
                break;
            }
        }

        $stream->write($nameToValue);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $this->toLogLoggableTraitImpl($stream);
    }
}
