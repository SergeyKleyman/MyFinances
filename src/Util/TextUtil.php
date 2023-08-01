<?php

declare(strict_types=1);

namespace MyFinances\Util;

final class TextUtil
{
    use StaticClassTrait;

    private const CR_AS_INT = 13;
    private const LF_AS_INT = 10;

    public static function isEmptyString(string $str): bool
    {
        return $str === '';
    }

    /**
     * @return iterable<int>
     *
     * @noinspection PhpUnused
     */
    public static function iterateOverChars(string $input): iterable
    {
        foreach (RangeUtil::generateUpTo(strlen($input)) as $i) {
            yield ord($input[$i]);
        }
    }

    private static function ifEndOfLineSeqGetLength(string $text, int $textLen, int $index): int
    {
        $charAsInt = ord($text[$index]);
        if ($charAsInt === self::CR_AS_INT && $index !== ($textLen - 1) && ord($text[$index + 1]) === self::LF_AS_INT) {
            return 2;
        }
        if ($charAsInt === self::CR_AS_INT || $charAsInt === self::LF_AS_INT) {
            return 1;
        }
        return 0;
    }

    /**
     * @return iterable<array{string, string}>
     *                                ^^^^^^----- end-of-line (empty for the last line)
     *                        ^^^^^^------------- line text without end-of-line
     */
    public static function iterateLinesEx(string $text): iterable
    {
        $lineStartPos = 0;
        $currentPos = $lineStartPos;
        $textLen = strlen($text);
        for (; $currentPos !== $textLen;) {
            $endOfLineSeqLength = self::ifEndOfLineSeqGetLength($text, $textLen, $currentPos);
            if ($endOfLineSeqLength === 0) {
                ++$currentPos;
                continue;
            }
            yield [substr($text, $lineStartPos, $currentPos - $lineStartPos) /* <- line text without end-of-line */, substr($text, $currentPos, $endOfLineSeqLength) /* <- end-of-line */];
            $lineStartPos = $currentPos + $endOfLineSeqLength;
            $currentPos = $lineStartPos;
        }

        yield [substr($text, $lineStartPos, $currentPos - $lineStartPos), '' /* <- end-of-line is always empty for the last line */];
    }

    /**
     * @return iterable<string>
     */
    public static function iterateLines(string $text, bool $keepEndOfLine): iterable
    {
        foreach (self::iterateLinesEx($text) as [$lineText, $endOfLine]) {
            yield $lineText . ($keepEndOfLine ? $endOfLine : '');
        }
    }

    public static function prefixEachLine(string $text, string $prefix): string
    {
        $result = '';
        foreach (self::iterateLines($text, /* keepEndOfLine */ true) as $line) {
            $result .= $prefix . $line;
        }
        return $result;
    }

    public static function combineWithSeparatorIfNotEmpty(string $separator, string $partToAppend): string
    {
        return (self::isEmptyString($partToAppend) ? '' : $separator) . $partToAppend;
    }

    public static function emptyIfNull(null|bool|float|int|string $input): string
    {
        return $input === null ? '' : strval($input);
    }
}
