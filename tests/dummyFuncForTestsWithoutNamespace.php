<?php

declare(strict_types=1);

use MyFinancesTests\Util\TestCaseBase;

const DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_FILE_NAME = __FILE__;
const DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_LINE_NUMBER = 20;

/**
 * @template TReturnValue
 *
 * @param callable(): TReturnValue $callable
 *
 * @return TReturnValue
 */
function dummyFuncForTestsWithoutNamespace(callable $callable)
{
    TestCaseBase::assertSame(DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_LINE_NUMBER, __LINE__ + 1);
    return $callable(); // DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_LINE_NUMBER should be this line number
}
