<?php

declare (strict_types=1);
namespace Rector\Core\DependencyInjection\CompilerPass;

use RectorPrefix20210503\Nette\Utils\Strings;
use Rector\Core\Contract\Rector\RectorInterface;
use Rector\Core\Exception\ShouldNotHappenException;
use RectorPrefix20210503\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use RectorPrefix20210503\Symfony\Component\DependencyInjection\ContainerBuilder;
final class VerifyRectorServiceExistsCompilerPass implements \RectorPrefix20210503\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @return void
     */
    public function process(\RectorPrefix20210503\Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder)
    {
        foreach ($containerBuilder->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if ($class === null) {
                continue;
            }
            if (!\RectorPrefix20210503\Nette\Utils\Strings::endsWith($class, 'Rector')) {
                continue;
            }
            if (!\is_a($class, \Rector\Core\Contract\Rector\RectorInterface::class, \true)) {
                throw new \Rector\Core\Exception\ShouldNotHappenException(\sprintf('Rector rule "%s" not found, please verify that the rule exists', $class));
            }
        }
    }
}