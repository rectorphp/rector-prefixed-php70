<?php

declare (strict_types=1);
namespace RectorPrefix20210526;

use RectorPrefix20210526\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
return static function (\RectorPrefix20210526\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();
    $services->defaults()->public()->autowire()->autoconfigure();
    $services->load('Rector\\CakePHP\\', __DIR__ . '/../src')->exclude([__DIR__ . '/../src/{Rector,ValueObject,Contract}']);
};
