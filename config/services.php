<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/**
 * Services partagés du bundle — à compléter (Messenger, factories) lors de la migration depuis temporal-dev.
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autoconfigure()
        ->autowire();
};
