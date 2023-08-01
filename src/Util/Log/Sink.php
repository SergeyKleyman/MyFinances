<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use DateTime;
use MyFinances\Util\ArrayUtil;
use MyFinances\Util\OsUtil;
use MyFinances\Util\SingletonInstanceTrait;
use MyFinances\Util\TextUtil;

final class Sink implements SinkInterface
{
    use SingletonInstanceTrait;

    /** @inheritDoc */
    public function consume(Record $statement): void
    {
        $formatted = $this->format($statement);
        $formattedWithEOL = $formatted . PHP_EOL;

        OutputDebugStringWriter::singletonInstance()->write($formattedWithEOL);

        if (OsUtil::isWindows()) {
            OutputDebugStringWriter::singletonInstance()->write($formattedWithEOL);
        } else {
            syslog($statement->level->toSyslogPriority(), $formatted);
        }
    }

    private function format(Record $statement): string
    {
        $formatted = '[MyFinances]';
        $formatted .= ' ' . (new DateTime())->format('Y-m-d H:i:s.v P');
        $formatted .= ' [' . $statement->level->toDisplayString() . ']';
        $formatted .= ' [PID: ' . getmypid() . ']';
        if (($dbgProcessName = ContextGlobalPerProcess::singletonInstance()->getName()) !== null) {
            $formatted .= ' [' . $dbgProcessName . ']';
        }
        $formatted .= ' [' . basename($statement->srcCodeFile) . ':' . $statement->srcCodeLine . ']';
        $formatted .= ' [' . $statement->srcCodeFunc . ']';
        if (!TextUtil::isEmptyString($statement->message)) {
            $formatted .= ' ' . $statement->message;
        }
        if (!ArrayUtil::isEmpty($statement->context)) {
            $formatted .= ' ' . LoggableToString::convert($statement->context);
        }
        return $formatted;
    }
}
