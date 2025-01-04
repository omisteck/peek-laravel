<?php

namespace Omisteck\Peek;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class ArgumentConverter
{
    public static function convertToPrimitive($argument)
    {
        if (is_null($argument)) {
            return null;
        }

        if (is_string($argument)) {
            return $argument;
        }

        if (is_int($argument)) {
            return $argument;
        }

        if (is_bool($argument)) {
            return $argument;
        }

        if (is_array($argument)) {
            return $argument;
        }

        $cloner = new VarCloner;

        $dumper = new CliDumper;

        $clonedArgument = $cloner->cloneVar($argument);

        return $dumper->dump($clonedArgument, true);
    }
}
