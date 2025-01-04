<?php

namespace Omisteck\Peek\Support;

use Exception;
use Omisteck\Peek\BasePeek;
use ReflectionFunction;

class ExceptionHandler
{
    public function catch(BasePeek $peek, $callback): BasePeek
    {
        $this->executeExceptionHandlerCallback($peek, $callback);

        if (! empty(BasePeek::$caughtExceptions)) {
            throw array_shift(BasePeek::$caughtExceptions);
        }

        return $peek;
    }

    protected function executeCallableExceptionHandler(BasePeek $peek, $callback, $rethrow = true): BasePeek
    {
        $paramType = $this->getParamType(new ReflectionFunction($callback));
        $expectedClasses = $this->getExpectedClasses($paramType);

        if (count($expectedClasses)) {
            $isExpected = false;

            foreach ($expectedClasses as $class) {
                $isExpected = $this->isExpectedExceptionClass($class);

                if ($isExpected) {
                    break;
                }
            }

            if (! $isExpected && ! $rethrow) {
                return $peek;
            }

            if (! $isExpected && $rethrow) {
                throw array_shift(BasePeek::$caughtExceptions);
            }
        }

        $exception = array_shift(BasePeek::$caughtExceptions);

        $callbackResult = $callback($exception, $peek);

        return $callbackResult instanceof BasePeek ? $callbackResult : $peek;
    }

    protected function isExpectedExceptionClass($expectedClass): bool
    {
        foreach (BasePeek::$caughtExceptions as $caughtException) {
            if (is_a($caughtException, $expectedClass, true)) {
                return true;
            }
        }

        return false;
    }

    protected function sendExceptionPayload(BasePeek $peek): BasePeek
    {
        $exception = array_shift(BasePeek::$caughtExceptions);

        return $peek->exception($exception);
    }

    protected function executeExceptionHandlerCallback(BasePeek $peek, $callback, $rethrow = true): BasePeek
    {
        if (empty(BasePeek::$caughtExceptions)) {
            return $peek;
        }

        if (is_callable($callback)) {
            return $this->executeCallableExceptionHandler($peek, $callback, $rethrow);
        }

        // support arrays of both class names and callables
        if (is_array($callback)) {
            return $this->executeArrayOfExceptionHandlers($peek, $callback) ?? $peek;
        }

        return $this->sendCallbackExceptionPayload($peek, $callback);
    }

    protected function executeArrayOfExceptionHandlers(BasePeek $peek, array $callbacks): ?BasePeek
    {
        foreach ($callbacks as $item) {
            $result = $this->executeExceptionHandlerCallback($peek, $item, false);

            // the array item handled the exception
            if (empty(BasePeek::$caughtExceptions)) {
                return $result instanceof BasePeek ? $result : $peek;
            }
        }

        return $peek;
    }

    protected function sendCallbackExceptionPayload(BasePeek $peek, $callback): BasePeek
    {
        if (! $callback) {
            return $this->sendExceptionPayload($peek);
        }

        // handle class names
        foreach (BasePeek::$caughtExceptions as $caughtException) {
            if (is_string($callback) && is_a($caughtException, $callback, true)) {
                return $this->sendExceptionPayload($peek);
            }
        }

        return $peek;
    }

    protected function getExpectedClasses($paramType): array
    {
        if (! $paramType) {
            return [Exception::class];
        }

        $result = is_a($paramType, '\\ReflectionUnionType') ? $paramType->getTypes() : [$paramType->getName()];

        return array_map(function ($type) {
            if (is_string($type)) {
                return $type;
            }

            return method_exists($type, 'getName') ? $type->getName() : get_class($type);
        }, $result);
    }

    protected function getParamType(ReflectionFunction $reflection)
    {
        $paramType = null;

        if ($reflection->getNumberOfParameters() > 0) {
            $paramType = $reflection->getParameters()[0]->getType();
        }

        return $paramType;
    }
}
