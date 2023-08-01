<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\Log\ContextGlobalPerProcess;
use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\Log\StdErrWriter;
use PHPUnit\Event\Test\AssertionFailed;
use PHPUnit\Event\Test\AssertionFailedSubscriber;
use PHPUnit\Runner\Extension\Extension as PhpUnitExtensionInterface;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Referenced in PHPUnit's configuration file
 *
 * @noinspection PhpUnused
 */
final class PhpUnitExtension implements PhpUnitExtensionInterface
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        ContextGlobalPerProcess::singletonInstance()->initName('PHPUnit');

        $facade->registerSubscriber(
            new class implements AssertionFailedSubscriber {
                public function notify(AssertionFailed $event): void
                {
                    PhpUnitExtension::printLogContextStackToStdErr();
                }
            }
        );
    }

    private static function printLineToStdErr(string $text): void
    {
        StdErrWriter::singletonInstance()->write($text . PHP_EOL);
    }

    public static function printLogContextStackToStdErr(): void
    {
        self::printLineToStdErr('---===###   LogContextStack begin   ###===---');
        self::printLineToStdErr(LoggableToString::convert(ContextStack::singletonInstance()->getContextsStack(), prettyPrint: true));
        self::printLineToStdErr('---===###   LogContextStack end   ###===---');
    }
}
