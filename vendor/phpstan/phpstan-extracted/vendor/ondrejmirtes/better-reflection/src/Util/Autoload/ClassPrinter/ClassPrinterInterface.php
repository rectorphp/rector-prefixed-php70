<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Util\Autoload\ClassPrinter;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
interface ClassPrinterInterface
{
    public function __invoke(\PHPStan\BetterReflection\Reflection\ReflectionClass $classInfo) : string;
}