<?php

declare (strict_types=1);
namespace RectorPrefix20210503\Symplify\EasyTesting\HttpKernel;

use RectorPrefix20210503\Symfony\Component\Config\Loader\LoaderInterface;
use RectorPrefix20210503\Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel;
final class EasyTestingKernel extends \RectorPrefix20210503\Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel
{
    /**
     * @return void
     */
    public function registerContainerConfiguration(\RectorPrefix20210503\Symfony\Component\Config\Loader\LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/../../config/config.php');
    }
}