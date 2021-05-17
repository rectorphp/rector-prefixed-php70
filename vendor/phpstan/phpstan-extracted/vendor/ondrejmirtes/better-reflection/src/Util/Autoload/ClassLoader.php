<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Util\Autoload;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;
use PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use PHPStan\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use function array_key_exists;
use function class_exists;
use function interface_exists;
use function spl_autoload_register;
use function trait_exists;
final class ClassLoader
{
    /** @var ReflectionClass[] */
    private $reflections = [];
    /** @var LoaderMethodInterface */
    private $loaderMethod;
    public function __construct(\PHPStan\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface $loaderMethod)
    {
        $this->loaderMethod = $loaderMethod;
        \spl_autoload_register($this, \true, \true);
    }
    /**
     * @throws ClassAlreadyLoaded
     * @throws ClassAlreadyRegistered
     * @return void
     */
    public function addClass(\PHPStan\BetterReflection\Reflection\ReflectionClass $reflectionClass)
    {
        if (\array_key_exists($reflectionClass->getName(), $this->reflections)) {
            throw \PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered::fromReflectionClass($reflectionClass);
        }
        if (\class_exists($reflectionClass->getName(), \false)) {
            throw \PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded::fromReflectionClass($reflectionClass);
        }
        $this->reflections[$reflectionClass->getName()] = $reflectionClass;
    }
    /**
     * @throws FailedToLoadClass
     */
    public function __invoke(string $classToLoad) : bool
    {
        if (!\array_key_exists($classToLoad, $this->reflections)) {
            return \false;
        }
        $this->loaderMethod->__invoke($this->reflections[$classToLoad]);
        if (!(\class_exists($classToLoad, \false) || \interface_exists($classToLoad, \false) || \trait_exists($classToLoad, \false))) {
            throw \PHPStan\BetterReflection\Util\Autoload\Exception\FailedToLoadClass::fromClassName($classToLoad);
        }
        return \true;
    }
}